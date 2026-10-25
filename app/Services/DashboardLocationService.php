<?php

namespace App\Services;

use App\Models\Land;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardLocationService
{
    private const CACHE_TTL_MINUTES = 30;

    private const STALE_TTL_HOURS = 24;

    /**
     * @return array<string, mixed>
     */
    public function loadForUser(User $user, ?int $selectedLandId = null): array
    {
        $lands = $user->lands()
            ->with(['crops:id,land_id,name'])
            ->orderBy('name')
            ->get();

        $locations = $lands->map(function (Land $land): array {
            $latitude = $this->coordinate($land->latitude, -90, 90);
            $longitude = $this->coordinate($land->longitude, -180, 180);
            $hasCoordinates = $latitude !== null && $longitude !== null;

            return [
                'id' => $land->id,
                'name' => $land->name,
                'location' => $land->location,
                'area' => (float) $land->area,
                'area_unit' => $land->area_unit,
                'status' => $land->status,
                'crops' => $land->crops->pluck('name')->values()->all(),
                'latitude' => $hasCoordinates ? $latitude : null,
                'longitude' => $hasCoordinates ? $longitude : null,
                'has_coordinates' => $hasCoordinates,
                'edit_url' => route('lands.edit', $land),
            ];
        });

        $selectedLocation = null;

        if ($selectedLandId !== null) {
            $selectedLocation = $locations->firstWhere('id', $selectedLandId);

            abort_if($selectedLocation === null, 404);
        } else {
            $selectedLocation = $locations->firstWhere('has_coordinates', true);
        }

        $coordinateCount = $locations->where('has_coordinates', true)->count();
        $locationStatus = match (true) {
            $lands->isEmpty() => 'no_locations',
            $coordinateCount === 0 => 'no_coordinates',
            $coordinateCount < $lands->count() => 'partial',
            default => 'success',
        };
        $weather = $selectedLocation !== null && $selectedLocation['has_coordinates']
            ? $this->weatherFor(
                (float) $selectedLocation['latitude'],
                (float) $selectedLocation['longitude'],
                $selectedLocation['location'],
            )
            : [
                'status' => $lands->isEmpty() ? 'no_location' : 'no_coordinates',
                'message' => $lands->isEmpty()
                    ? 'Tambahkan lahan untuk melihat cuaca.'
                    : 'Simpan titik koordinat pada lahan untuk melihat cuaca di lokasi tersebut.',
            ];

        return [
            'location_status' => $locationStatus,
            'locations' => $locations->values()->all(),
            'selected_land_id' => $selectedLocation['id'] ?? null,
            'weather' => $weather,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function weatherFor(float $latitude, float $longitude, string $location): array
    {
        $parameters = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current' => implode(',', [
                'temperature_2m',
                'relative_humidity_2m',
                'apparent_temperature',
                'is_day',
                'precipitation',
                'weather_code',
                'wind_speed_10m',
                'wind_direction_10m',
            ]),
            'hourly' => implode(',', [
                'temperature_2m',
                'precipitation_probability',
                'precipitation',
                'weather_code',
            ]),
            'daily' => implode(',', [
                'weather_code',
                'temperature_2m_max',
                'temperature_2m_min',
                'precipitation_sum',
                'precipitation_probability_max',
                'wind_speed_10m_max',
            ]),
            'forecast_days' => 7,
            'timezone' => 'auto',
        ];
        $cacheKey = 'dashboard_weather_v3_'.hash('sha256', json_encode([
            config('services.open_meteo.forecast_url'),
            number_format($latitude, 7, '.', ''),
            number_format($longitude, 7, '.', ''),
            $parameters,
        ], JSON_THROW_ON_ERROR));
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && isset($cached['fetched_at'])
            && now()->subMinutes(self::CACHE_TTL_MINUTES)->lt($cached['fetched_at'])) {
            return [...$cached, 'location' => $location, 'data_status' => 'cached', 'stale' => false];
        }

        try {
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->withOptions($this->httpOptions())
                ->get(config('services.open_meteo.forecast_url'), $parameters);

            if (! $response->successful()) {
                Log::warning('Open-Meteo forecast request failed.', [
                    'status' => $response->status(),
                ]);

                return $this->weatherFailure($cached, $location);
            }

            $payload = $response->json();

            if (! is_array($payload) || ! $this->validCurrentWeather($payload['current'] ?? null)) {
                Log::warning('Open-Meteo returned an invalid current weather payload.');

                return $this->weatherFailure($cached, $location);
            }

            $weather = $this->formatWeather($payload, $location);
            Cache::put($cacheKey, $weather, now()->addHours(self::STALE_TTL_HOURS));

            return [...$weather, 'data_status' => 'live', 'stale' => false];
        } catch (ConnectionException $exception) {
            Log::warning('Open-Meteo forecast connection failed.', [
                'exception' => $exception->getMessage(),
            ]);

            return $this->weatherFailure($cached, $location);
        } catch (Throwable $exception) {
            Log::error('Open-Meteo forecast could not be processed.', [
                'exception' => $exception->getMessage(),
            ]);

            return $this->weatherFailure($cached, $location);
        }
    }

    /**
     * @param  array<string, mixed>|null  $current
     */
    private function validCurrentWeather(mixed $current): bool
    {
        return is_array($current)
            && is_numeric($current['temperature_2m'] ?? null)
            && is_numeric($current['weather_code'] ?? null)
            && is_string($current['time'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function formatWeather(array $payload, string $location): array
    {
        $current = $payload['current'];
        $hourly = $payload['hourly'] ?? [];
        $daily = $payload['daily'] ?? [];
        $timezone = is_string($payload['timezone'] ?? null) ? $payload['timezone'] : 'UTC';
        $condition = $this->condition((int) $current['weather_code']);
        $hourlyForecast = $this->hourlyForecast($hourly, (string) $current['time']);
        $forecast = $this->dailyForecast($daily);
        $rainProbability = $hourlyForecast[0]['rain_probability'] ?? null;

        return [
            'status' => 'success',
            'location' => $location,
            'timezone' => $timezone,
            'units' => [
                'temperature' => $payload['current_units']['temperature_2m'] ?? '°C',
                'precipitation' => $payload['current_units']['precipitation'] ?? 'mm',
                'wind_speed' => $payload['current_units']['wind_speed_10m'] ?? 'km/j',
            ],
            'temperature' => (float) $current['temperature_2m'],
            'apparent_temperature' => $this->nullableNumber($current['apparent_temperature'] ?? null),
            'condition' => $condition['label'],
            'icon' => $condition['icon'],
            'humidity' => $this->nullableNumber($current['relative_humidity_2m'] ?? null),
            'precipitation' => $this->nullableNumber($current['precipitation'] ?? null),
            'rain_probability' => $rainProbability,
            'wind_speed' => $this->nullableNumber($current['wind_speed_10m'] ?? null),
            'wind_direction' => $this->nullableNumber($current['wind_direction_10m'] ?? null),
            'current_time' => $current['time'],
            'fetched_at' => now()->toISOString(),
            'hourly' => $hourlyForecast,
            'forecast' => $forecast,
        ];
    }

    /**
     * @param  array<string, mixed>  $hourly
     * @return array<int, array<string, mixed>>
     */
    private function hourlyForecast(array $hourly, string $currentTime): array
    {
        $times = $hourly['time'] ?? [];
        $startIndex = null;

        foreach ($times as $index => $time) {
            if (is_string($time) && $time >= $currentTime) {
                $startIndex = (int) $index;
                break;
            }
        }

        if ($startIndex === null) {
            return [];
        }

        $forecast = [];

        for ($index = $startIndex; $index < min(count($times), $startIndex + 24); $index++) {
            if (! is_string($times[$index] ?? null)) {
                continue;
            }

            $forecast[] = [
                'time' => $times[$index],
                'temperature' => $this->nullableNumber($hourly['temperature_2m'][$index] ?? null),
                'rain_probability' => $this->nullableNumber($hourly['precipitation_probability'][$index] ?? null),
                'precipitation' => $this->nullableNumber($hourly['precipitation'][$index] ?? null),
                'condition' => is_numeric($hourly['weather_code'][$index] ?? null)
                    ? $this->condition((int) $hourly['weather_code'][$index])['label']
                    : null,
            ];
        }

        return $forecast;
    }

    /**
     * @param  array<string, mixed>  $daily
     * @return array<int, array<string, mixed>>
     */
    private function dailyForecast(array $daily): array
    {
        $forecast = [];

        foreach ($daily['time'] ?? [] as $index => $date) {
            if (! is_string($date)) {
                continue;
            }

            $condition = is_numeric($daily['weather_code'][$index] ?? null)
                ? $this->condition((int) $daily['weather_code'][$index])
                : ['label' => 'Tidak diketahui', 'icon' => 'cloud'];
            $forecast[] = [
                'date' => $date,
                'condition' => $condition['label'],
                'icon' => $condition['icon'],
                'temperature_max' => $this->nullableNumber($daily['temperature_2m_max'][$index] ?? null),
                'temperature_min' => $this->nullableNumber($daily['temperature_2m_min'][$index] ?? null),
                'precipitation' => $this->nullableNumber($daily['precipitation_sum'][$index] ?? null),
                'rain_probability' => $this->nullableNumber($daily['precipitation_probability_max'][$index] ?? null),
                'wind_speed' => $this->nullableNumber($daily['wind_speed_10m_max'][$index] ?? null),
            ];
        }

        return $forecast;
    }

    /**
     * @param  array<string, mixed>|null  $cached
     * @return array<string, mixed>
     */
    private function weatherFailure(?array $cached, string $location): array
    {
        if (is_array($cached) && isset($cached['fetched_at'])) {
            return [
                ...$cached,
                'location' => $location,
                'data_status' => 'stale',
                'stale' => true,
                'message' => 'Penyedia cuaca tidak dapat dijangkau. Menampilkan data tersimpan yang mungkin sudah kedaluwarsa.',
            ];
        }

        return [
            'status' => 'unavailable',
            'message' => 'Data cuaca sementara tidak tersedia. Coba muat ulang beberapa saat lagi.',
        ];
    }

    /**
     * @return array{label: string, icon: string}
     */
    private function condition(int $code): array
    {
        return match (true) {
            $code === 0 => ['label' => 'Cerah', 'icon' => 'sun'],
            in_array($code, [1, 2], true) => ['label' => 'Cerah berawan', 'icon' => 'cloud-sun'],
            $code === 3 => ['label' => 'Mendung', 'icon' => 'cloud'],
            in_array($code, [45, 48], true) => ['label' => 'Berkabut', 'icon' => 'cloud'],
            in_array($code, [51, 53, 55, 56, 57], true) => ['label' => 'Gerimis', 'icon' => 'rain'],
            in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true) => ['label' => 'Hujan', 'icon' => 'rain'],
            in_array($code, [71, 73, 75, 77, 85, 86], true) => ['label' => 'Salju', 'icon' => 'cloud'],
            in_array($code, [95, 96, 99], true) => ['label' => 'Badai petir', 'icon' => 'storm'],
            default => ['label' => 'Tidak diketahui', 'icon' => 'cloud'],
        };
    }

    private function nullableNumber(mixed $value): float|int|null
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function coordinate(mixed $value, float $minimum, float $maximum): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $coordinate = (float) $value;

        return $coordinate >= $minimum && $coordinate <= $maximum ? $coordinate : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function httpOptions(): array
    {
        $caBundle = config('services.open_meteo.ca_bundle');

        return is_string($caBundle) && $caBundle !== '' && is_file($caBundle)
            ? ['verify' => $caBundle]
            : [];
    }
}
