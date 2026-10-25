<?php

use App\Models\Land;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function dashboardWeatherPayload(float $temperature = 27.4): array
{
    $times = [];
    $hourlyTemperature = [];
    $rainProbability = [];
    $hourlyPrecipitation = [];
    $hourlyCodes = [];

    for ($hour = 0; $hour < 24; $hour++) {
        $times[] = sprintf('2026-10-15T%02d:00', $hour);
        $hourlyTemperature[] = $temperature + ($hour / 10);
        $rainProbability[] = $hour === 10 ? 35 : 10;
        $hourlyPrecipitation[] = $hour === 10 ? 0.4 : 0;
        $hourlyCodes[] = 3;
    }

    return [
        'timezone' => 'Asia/Jakarta',
        'current_units' => [
            'temperature_2m' => '°C',
            'precipitation' => 'mm',
            'wind_speed_10m' => 'km/h',
        ],
        'current' => [
            'time' => '2026-10-15T10:00',
            'temperature_2m' => $temperature,
            'relative_humidity_2m' => 78,
            'apparent_temperature' => 30.2,
            'weather_code' => 3,
            'precipitation' => 0.2,
            'wind_speed_10m' => 8.2,
            'wind_direction_10m' => 135,
        ],
        'hourly' => [
            'time' => $times,
            'temperature_2m' => $hourlyTemperature,
            'precipitation_probability' => $rainProbability,
            'precipitation' => $hourlyPrecipitation,
            'weather_code' => $hourlyCodes,
        ],
        'daily' => [
            'time' => [
                '2026-10-15',
                '2026-10-16',
                '2026-10-17',
                '2026-10-18',
                '2026-10-19',
                '2026-10-20',
                '2026-10-21',
            ],
            'weather_code' => [3, 61, 0, 2, 95, 1, 3],
            'temperature_2m_max' => [30, 28, 31, 29, 27, 30, 30],
            'temperature_2m_min' => [21, 22, 20, 21, 22, 20, 21],
            'precipitation_sum' => [2.4, 5.1, 0, 0.2, 15, 0, 0],
            'precipitation_probability_max' => [55, 80, 10, 20, 90, 5, 10],
            'wind_speed_10m_max' => [10, 12, 8, 8, 18, 7, 9],
        ],
    ];
}

function createDashboardLand(User $user, array $attributes = []): Land
{
    return $user->lands()->create(array_merge([
        'name' => 'Kebun Saya',
        'location' => 'Bandung',
        'latitude' => '-6.9000000',
        'longitude' => '107.6000000',
        'area' => 1,
        'area_unit' => 'hektare',
        'status' => 'aktif',
    ], $attributes));
}

it('does not contact location providers while rendering the dashboard', function () {
    Http::preventStrayRequests();
    Http::fake();
    createDashboardLand(User::factory()->create());

    $this->actingAs(User::query()->first())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Permintaan lokasi dan cuaca eksternal hanya dilakukan setelah tombol ditekan; tile peta dasar dimuat dari OpenStreetMap saat halaman dibuka.');

    Http::assertNothingSent();
});

it('returns no locations without contacting external providers', function () {
    Http::preventStrayRequests();
    Http::fake();

    $this->actingAs(User::factory()->create())
        ->postJson(route('dashboard.location-data'))
        ->assertOk()
        ->assertJsonPath('location_status', 'no_locations')
        ->assertJsonPath('weather.status', 'no_location')
        ->assertJsonPath('locations', []);

    Http::assertNothingSent();
});

it('returns only the signed-in users lands and the expanded weather forecast', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake(['https://api.open-meteo.com/v1/forecast*' => Http::response(dashboardWeatherPayload())]);
    $user = User::factory()->create();
    createDashboardLand($user, ['name' => 'Kebun Milik Saya']);
    createDashboardLand(User::factory()->create(), ['name' => 'Lahan Rahasia']);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'))
        ->assertOk()
        ->assertJsonPath('location_status', 'success')
        ->assertJsonCount(1, 'locations')
        ->assertJsonPath('locations.0.name', 'Kebun Milik Saya')
        ->assertJsonPath('locations.0.has_coordinates', true)
        ->assertJsonPath('weather.status', 'success')
        ->assertJsonPath('weather.temperature', 27.4)
        ->assertJsonPath('weather.apparent_temperature', 30.2)
        ->assertJsonPath('weather.condition', 'Mendung')
        ->assertJsonPath('weather.humidity', 78)
        ->assertJsonPath('weather.rain_probability', 35)
        ->assertJsonPath('weather.precipitation', 0.2)
        ->assertJsonPath('weather.wind_speed', 8.2)
        ->assertJsonPath('weather.wind_direction', 135)
        ->assertJsonPath('weather.timezone', 'Asia/Jakarta')
        ->assertJsonCount(7, 'weather.forecast')
        ->assertJsonCount(14, 'weather.hourly')
        ->assertJsonPath('weather.forecast.0.rain_probability', 55)
        ->assertDontSee('Lahan Rahasia');

    Http::assertSentCount(1);
    Http::assertSent(fn (ClientRequest $request): bool => str_contains($request->url(), '/v1/forecast')
        && (float) ($request->data()['latitude'] ?? 0) === -6.9
        && (float) ($request->data()['longitude'] ?? 0) === 107.6
        && ($request->data()['timezone'] ?? null) === 'auto'
        && ($request->data()['forecast_days'] ?? null) === 7);
});

it('uses saved coordinates for the selected land and not a guessed address location', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake(['https://api.open-meteo.com/v1/forecast*' => Http::response(dashboardWeatherPayload())]);
    $user = User::factory()->create();
    $land = createDashboardLand($user, [
        'name' => 'Kebun Titik Manual',
        'location' => 'Alamat kebun saja',
        'latitude' => '-6.9147440',
        'longitude' => '107.6098100',
    ]);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'), ['land_id' => $land->id])
        ->assertOk()
        ->assertJsonPath('selected_land_id', $land->id)
        ->assertJsonPath('locations.0.latitude', -6.914744)
        ->assertJsonPath('locations.0.longitude', 107.60981)
        ->assertJsonPath('weather.location', 'Alamat kebun saja');

    Http::assertSentCount(1);
    Http::assertSent(fn (ClientRequest $request): bool => (float) $request->data()['latitude'] === -6.914744
        && (float) $request->data()['longitude'] === 107.60981
        && ! array_key_exists('name', $request->data()));
});

it('does not send a land without saved coordinates to any location provider', function () {
    Http::preventStrayRequests();
    Http::fake();
    $user = User::factory()->create();
    createDashboardLand($user, ['latitude' => null, 'longitude' => null]);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'))
        ->assertOk()
        ->assertJsonPath('location_status', 'no_coordinates')
        ->assertJsonPath('locations.0.has_coordinates', false)
        ->assertJsonPath('locations.0.latitude', null)
        ->assertJsonPath('weather.status', 'no_coordinates');

    Http::assertNothingSent();
});

it('does not use invalid stored coordinates for map markers or weather', function () {
    Http::preventStrayRequests();
    Http::fake();
    $user = User::factory()->create();
    createDashboardLand($user, ['latitude' => '91.0000000', 'longitude' => '107.6000000']);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'))
        ->assertOk()
        ->assertJsonPath('location_status', 'no_coordinates')
        ->assertJsonPath('locations.0.has_coordinates', false)
        ->assertJsonPath('weather.status', 'no_coordinates');

    Http::assertNothingSent();
});

it('rejects a selected land owned by another user', function () {
    Http::preventStrayRequests();
    Http::fake();
    $user = User::factory()->create();
    $otherLand = createDashboardLand(User::factory()->create(), ['name' => 'Lahan Rahasia']);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'), ['land_id' => $otherLand->id])
        ->assertNotFound();

    Http::assertNothingSent();
});

it('validates the selected land identifier', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('dashboard.location-data'), ['land_id' => 'not-a-number'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('land_id');
});

it('reports partial map coverage when some lands have no saved coordinates', function () {
    Http::preventStrayRequests();
    Http::fake(['https://api.open-meteo.com/v1/forecast*' => Http::response(dashboardWeatherPayload())]);
    $user = User::factory()->create();
    createDashboardLand($user, ['name' => 'Lahan Bertitik']);
    createDashboardLand($user, [
        'name' => 'Lahan Tanpa Titik',
        'latitude' => null,
        'longitude' => null,
    ]);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'))
        ->assertOk()
        ->assertJsonPath('location_status', 'partial')
        ->assertJsonCount(2, 'locations')
        ->assertJsonPath('locations.0.has_coordinates', true)
        ->assertJsonPath('locations.1.has_coordinates', false);

    Http::assertSentCount(1);
});

it('returns an explicit unavailable status when forecast responds with an HTTP error', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake(['https://api.open-meteo.com/v1/forecast*' => Http::response(['reason' => 'Unavailable'], 503)]);
    $user = User::factory()->create();
    createDashboardLand($user);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'))
        ->assertOk()
        ->assertJsonPath('weather.status', 'unavailable')
        ->assertJsonMissingPath('weather.temperature');
});

it('returns unavailable when forecast responds with an invalid weather payload', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake(['https://api.open-meteo.com/v1/forecast*' => Http::response(['current' => 'invalid'], 200)]);
    $user = User::factory()->create();
    createDashboardLand($user);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'))
        ->assertOk()
        ->assertJsonPath('weather.status', 'unavailable');
});

it('returns an explicit weather fallback when the forecast provider times out', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake([
        'https://api.open-meteo.com/v1/forecast*' => fn () => throw new ConnectionException('Request timed out'),
    ]);
    $user = User::factory()->create();
    createDashboardLand($user);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'))
        ->assertOk()
        ->assertJsonPath('weather.status', 'unavailable');
});

it('uses a fresh cached weather response for matching exact coordinates', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake(['https://api.open-meteo.com/v1/forecast*' => Http::response(dashboardWeatherPayload())]);
    $user = User::factory()->create();
    createDashboardLand($user);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'))
        ->assertJsonPath('weather.data_status', 'live');
    $this->postJson(route('dashboard.location-data'))
        ->assertJsonPath('weather.data_status', 'cached');

    Http::assertSentCount(1);
});

it('does not expose a cached address label to another user at the same coordinates', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake(['https://api.open-meteo.com/v1/forecast*' => Http::response(dashboardWeatherPayload())]);
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    createDashboardLand($firstUser, ['location' => 'Alamat pribadi pertama']);
    createDashboardLand($secondUser, ['location' => 'Alamat pribadi kedua']);

    $this->actingAs($firstUser)
        ->postJson(route('dashboard.location-data'))
        ->assertJsonPath('weather.location', 'Alamat pribadi pertama');

    $this->actingAs($secondUser)
        ->postJson(route('dashboard.location-data'))
        ->assertJsonPath('weather.data_status', 'cached')
        ->assertJsonPath('weather.location', 'Alamat pribadi kedua');

    Http::assertSentCount(1);
});

it('keeps weather cache entries separate for different land coordinates', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake(['https://api.open-meteo.com/v1/forecast*' => Http::sequence()
        ->push(dashboardWeatherPayload(25))
        ->push(dashboardWeatherPayload(31))]);
    $user = User::factory()->create();
    $firstLand = createDashboardLand($user);
    $secondLand = createDashboardLand($user, [
        'name' => 'Kebun Kedua',
        'latitude' => '-7.1000000',
        'longitude' => '108.2000000',
    ]);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'), ['land_id' => $firstLand->id])
        ->assertJsonPath('weather.temperature', 25);
    $this->postJson(route('dashboard.location-data'), ['land_id' => $secondLand->id])
        ->assertJsonPath('weather.temperature', 31);

    Http::assertSentCount(2);
});

it('labels and serves stale weather after a forecast provider failure', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake(['https://api.open-meteo.com/v1/forecast*' => Http::sequence()
        ->push(dashboardWeatherPayload())
        ->pushStatus(503)]);
    $user = User::factory()->create();
    createDashboardLand($user);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'))
        ->assertJsonPath('weather.data_status', 'live');

    $this->travel(31)->minutes();

    $this->postJson(route('dashboard.location-data'))
        ->assertOk()
        ->assertJsonPath('weather.status', 'success')
        ->assertJsonPath('weather.stale', true)
        ->assertJsonPath('weather.data_status', 'stale')
        ->assertJsonPath('weather.temperature', 27.4)
        ->assertJsonPath('weather.message', 'Penyedia cuaca tidak dapat dijangkau. Menampilkan data tersimpan yang mungkin sudah kedaluwarsa.');
});

it('returns no location data for an empty saved address without geocoding it', function () {
    Http::preventStrayRequests();
    Http::fake();
    $user = User::factory()->create();
    createDashboardLand($user, [
        'location' => '',
        'latitude' => null,
        'longitude' => null,
    ]);

    $this->actingAs($user)
        ->postJson(route('dashboard.location-data'))
        ->assertOk()
        ->assertJsonPath('location_status', 'no_coordinates')
        ->assertJsonPath('weather.status', 'no_coordinates');

    Http::assertNothingSent();
});

it('requires an authenticated user to request location and weather data', function () {
    $this->postJson(route('dashboard.location-data'))
        ->assertUnauthorized();
});

it('limits repeated dashboard location requests', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    for ($request = 0; $request < 6; $request++) {
        $this->postJson(route('dashboard.location-data'))->assertOk();
    }

    $this->postJson(route('dashboard.location-data'))
        ->assertStatus(429);
});
