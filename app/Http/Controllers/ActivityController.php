<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityCalendarRequest;
use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use App\Models\Crop;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    private const TIMEZONE = 'Asia/Jakarta';

    public function index(Request $request): View
    {
        $activities = $this->ownedActivities($request->user())
            ->with('crop.land')
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->orderBy('id')
            ->paginate(10);

        return view('activities.index', ['activities' => $activities]);
    }

    public function calendar(ActivityCalendarRequest $request): JsonResponse|View
    {
        $filters = $request->validated();
        $today = CarbonImmutable::now(self::TIMEZONE)->startOfDay();
        $monthValue = $filters['month']
            ?? (isset($filters['date'])
                ? substr($filters['date'], 0, 7)
                : $today->format('Y-m'));
        $month = CarbonImmutable::createFromFormat('!Y-m', $monthValue, self::TIMEZONE);
        $selectedDate = isset($filters['date'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['date'], self::TIMEZONE)
            : ($today->format('Y-m') === $monthValue ? $today : $month);
        $filteredActivities = $this->filteredOwnedActivities($request->user(), $filters)
            ->with('crop:id,name,land_id', 'crop.land:id,name');
        $calendarActivities = (clone $filteredActivities)
            ->whereBetween('scheduled_date', [
                $month->startOfMonth()->toDateString(),
                $month->endOfMonth()->toDateString(),
            ])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->orderBy('id')
            ->get();

        $calendarData = [
            'month' => $monthValue,
            'selected_date' => $selectedDate->toDateString(),
            'today' => $today->toDateString(),
            'tomorrow' => $today->addDay()->toDateString(),
            'calendar_activities' => $calendarActivities,
            'selected_date_activities' => $calendarActivities
                ->filter(fn (Activity $activity): bool => $activity->scheduled_date->toDateString() === $selectedDate->toDateString())
                ->values(),
            'today_activities' => (clone $filteredActivities)
                ->whereDate('scheduled_date', $today->toDateString())
                ->orderBy('scheduled_time')
                ->orderBy('id')
                ->get(),
            'tomorrow_activities' => (clone $filteredActivities)
                ->whereDate('scheduled_date', $today->addDay()->toDateString())
                ->orderBy('scheduled_time')
                ->orderBy('id')
                ->get(),
            'overdue_activities' => (clone $filteredActivities)
                ->where('status', 'belum_dilakukan')
                ->whereDate('scheduled_date', '<', $today->toDateString())
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->orderBy('id')
                ->get(),
        ];

        if ($request->expectsJson()) {
            return response()->json($calendarData);
        }

        $lands = $request->user()->lands()
            ->orderBy('name')
            ->get(['id', 'name']);
        $crops = $this->ownedCrops($request->user())
            ->when(
                isset($filters['land_id']),
                fn (Builder $query) => $query->where('land_id', $filters['land_id']),
            )
            ->get(['id', 'name', 'land_id']);

        return view('activities.calendar', [
            ...$calendarData,
            'lands' => $lands,
            'crops' => $crops,
        ]);
    }

    public function create(Request $request): View
    {
        return view('activities.form', [
            'activity' => new Activity([
                'scheduled_date' => today()->toDateString(),
                'status' => 'belum_dilakukan',
            ]),
            'crops' => $this->ownedCrops($request->user())->get(),
        ]);
    }

    public function store(ActivityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $crop = $this->ownedCrops($request->user())->findOrFail($data['crop_id']);
        $crop->activities()->create($data);

        return to_route('activities.index')->with('status', 'Jadwal aktivitas berhasil ditambahkan.');
    }

    public function edit(Request $request, Activity $activity): View
    {
        $activity = $this->ownedActivities($request->user())
            ->with('crop.land')
            ->findOrFail($activity->getKey());

        return view('activities.form', [
            'activity' => $activity,
            'crops' => $this->ownedCrops($request->user())->get(),
        ]);
    }

    public function update(ActivityRequest $request, Activity $activity): RedirectResponse
    {
        $activity = $this->ownedActivities($request->user())->findOrFail($activity->getKey());
        $data = $request->validated();
        $crop = $this->ownedCrops($request->user())->findOrFail($data['crop_id']);

        $activity->update([
            ...$data,
            'crop_id' => $crop->getKey(),
        ]);

        return to_route('activities.index')->with('status', 'Jadwal aktivitas berhasil diperbarui.');
    }

    public function destroy(Request $request, Activity $activity): RedirectResponse
    {
        $activity = $this->ownedActivities($request->user())->findOrFail($activity->getKey());
        $activity->delete();

        return to_route('activities.index')->with('status', 'Jadwal aktivitas berhasil dihapus.');
    }

    private function ownedActivities(User $user): Builder
    {
        return Activity::query()
            ->whereHas('crop.land', fn (Builder $query) => $query->where('user_id', $user->getKey()));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredOwnedActivities(User $user, array $filters): Builder
    {
        $activities = $this->ownedActivities($user);

        if (isset($filters['land_id'])) {
            $activities->whereHas('crop', fn (Builder $query) => $query->where('land_id', $filters['land_id']));
        }

        if (isset($filters['crop_id'])) {
            $activities->where('crop_id', $filters['crop_id']);
        }

        if (isset($filters['status'])) {
            $activities->where('status', $filters['status']);
        }

        return $activities;
    }

    private function ownedCrops(User $user): Builder
    {
        return Crop::query()
            ->whereHas('land', fn (Builder $query) => $query->where('user_id', $user->getKey()))
            ->with('land')
            ->orderBy('name');
    }
}
