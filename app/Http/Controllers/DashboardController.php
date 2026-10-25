<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Crop;
use App\Models\Harvest;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $userId = $user->getAuthIdentifier();
        $now = CarbonImmutable::now('Asia/Jakarta');
        $today = $now->startOfDay();
        $ownedSales = Sale::query()
            ->whereHas('harvest.crop.land', fn (Builder $query) => $query->where('user_id', $userId));
        $ownedExpenses = $user->expenses();
        $ownedCrops = Crop::query()
            ->whereHas('land', fn (Builder $query) => $query->where('user_id', $userId));
        $ownedActivities = Activity::query()
            ->whereHas('crop.land', fn (Builder $query) => $query->where('user_id', $userId));
        $ownedHarvests = Harvest::query()
            ->whereHas('crop.land', fn (Builder $query) => $query->where('user_id', $userId));
        $activityToday = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $todayActivitiesQuery = (clone $ownedActivities)
            ->where('status', '!=', 'selesai')
            ->whereDate('scheduled_date', $activityToday->toDateString());
        $futureActivitiesQuery = (clone $ownedActivities)
            ->where('status', '!=', 'selesai')
            ->whereDate('scheduled_date', '>', $activityToday->toDateString());
        $overdueActivitiesQuery = (clone $ownedActivities)
            ->where('status', '!=', 'selesai')
            ->whereDate('scheduled_date', '<', $activityToday->toDateString());
        $upcomingHarvestsQuery = (clone $ownedCrops)
            ->whereIn('status', ['ditanam', 'perawatan', 'siap_panen'])
            ->whereDate('estimated_harvest_date', '>=', $today->toDateString())
            ->whereDate('estimated_harvest_date', '<=', $today->addDays(30)->toDateString());
        $upcomingActivitiesQuery = (clone $ownedActivities)
            ->where('status', 'belum_dilakukan')
            ->whereDate('scheduled_date', '>=', $today->toDateString());
        $lowStockProductsQuery = $user->products()
            ->whereColumn('stock', '<=', 'minimum_stock');
        $firstMonth = $now->startOfMonth()->subMonths(5);
        $lastDay = $now->toDateString();
        $monthKeys = [];
        $monthLabels = [];
        $incomeByMonth = [];
        $expensesByMonth = [];

        foreach (range(0, 5) as $monthOffset) {
            $month = $firstMonth->addMonths($monthOffset);
            $monthKey = $month->format('Y-m');
            $monthKeys[] = $monthKey;
            $monthLabels[] = $month->locale('id')->translatedFormat('M y');
            $incomeByMonth[$monthKey] = 0.0;
            $expensesByMonth[$monthKey] = 0.0;
        }

        $firstDay = $firstMonth->toDateString();

        $incomeRows = (clone $ownedSales)
            ->whereDate('sale_date', '>=', $firstDay)
            ->whereDate('sale_date', '<=', $lastDay)
            ->selectRaw('DATE(sale_date) as period_date, SUM(total) as amount')
            ->groupByRaw('DATE(sale_date)')
            ->get();

        $expenseRows = (clone $ownedExpenses)
            ->whereDate('expense_date', '>=', $firstDay)
            ->whereDate('expense_date', '<=', $lastDay)
            ->selectRaw('DATE(expense_date) as period_date, SUM(amount) as amount')
            ->groupByRaw('DATE(expense_date)')
            ->get();

        $this->addMonthlyTotals($incomeRows, $incomeByMonth);
        $this->addMonthlyTotals($expenseRows, $expensesByMonth);

        $harvestRows = (clone $ownedHarvests)
            ->whereDate('harvest_date', '>=', $firstDay)
            ->whereDate('harvest_date', '<=', $lastDay)
            ->selectRaw('DATE(harvest_date) as period_date, unit, SUM(quantity) as quantity')
            ->groupByRaw('DATE(harvest_date), unit')
            ->orderBy('unit')
            ->get();
        $harvestsByUnit = [];

        foreach ($harvestRows as $row) {
            $monthKey = CarbonImmutable::parse($row->period_date)->format('Y-m');
            $harvestsByUnit[$row->unit][$monthKey] = ($harvestsByUnit[$row->unit][$monthKey] ?? 0)
                + (float) $row->quantity;
        }

        $financialChart = [
            'labels' => $monthLabels,
            'income' => array_values($incomeByMonth),
            'expenses' => array_values($expensesByMonth),
        ];
        $harvestChart = [
            'labels' => $monthLabels,
            'datasets' => collect($harvestsByUnit)
                ->map(fn (array $quantities, string $unit): array => [
                    'label' => $unit,
                    'data' => array_map(fn (string $key): float => (float) ($quantities[$key] ?? 0), $monthKeys),
                ])
                ->values()
                ->all(),
        ];
        $income = (clone $ownedSales)->sum('total');
        $expenseTotal = (clone $ownedExpenses)->sum('amount');

        return view('dashboard', [
            'dashboardDate' => $now,
            'income' => $income,
            'salesCount' => (clone $ownedSales)->count(),
            'expenseTotal' => $expenseTotal,
            'profit' => $income - $expenseTotal,
            'expenseCount' => (clone $ownedExpenses)->count(),
            'activeLandCount' => $user->lands()->where('status', 'aktif')->count(),
            'plantedCropCount' => (clone $ownedCrops)
                ->whereIn('status', ['ditanam', 'perawatan'])
                ->count(),
            'upcomingHarvestCount' => (clone $upcomingHarvestsQuery)->count(),
            'upcomingHarvests' => (clone $upcomingHarvestsQuery)
                ->with('land:id,name')
                ->orderBy('estimated_harvest_date')
                ->limit(5)
                ->get(),
            'upcomingActivityCount' => (clone $upcomingActivitiesQuery)->count(),
            'upcomingActivities' => (clone $upcomingActivitiesQuery)
                ->with('crop:id,name,land_id', 'crop.land:id,name')
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->limit(5)
                ->get(),
            'todayActivitiesCount' => (clone $todayActivitiesQuery)->count(),
            'todayActivities' => (clone $todayActivitiesQuery)
                ->with('crop:id,name,land_id', 'crop.land:id,name')
                ->orderBy('scheduled_time')
                ->orderBy('id')
                ->limit(5)
                ->get(),
            'futureActivitiesCount' => (clone $futureActivitiesQuery)->count(),
            'futureActivities' => (clone $futureActivitiesQuery)
                ->with('crop:id,name,land_id', 'crop.land:id,name')
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->orderBy('id')
                ->limit(5)
                ->get(),
            'overdueActivitiesCount' => (clone $overdueActivitiesQuery)->count(),
            'overdueActivities' => (clone $overdueActivitiesQuery)
                ->with('crop:id,name,land_id', 'crop.land:id,name')
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->orderBy('id')
                ->limit(5)
                ->get(),
            'lowStockProductCount' => (clone $lowStockProductsQuery)->count(),
            'totalProductCount' => $user->products()->count(),
            'lowStockProducts' => (clone $lowStockProductsQuery)
                ->orderBy('stock')
                ->get(),
            'recentSales' => (clone $ownedSales)
                ->with('harvest.crop')
                ->orderByDesc('sale_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'recentExpenses' => (clone $ownedExpenses)
                ->with(['crop:id,name', 'product:id,name'])
                ->orderByDesc('expense_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'financialChart' => $financialChart,
            'harvestChart' => $harvestChart,
            'chartPeriodLabel' => $firstMonth->locale('id')->translatedFormat('F Y').' – '.$now->locale('id')->translatedFormat('F Y'),
            'landsForMap' => $user->lands()
                ->orderBy('name')
                ->get(['id', 'name', 'location', 'area', 'area_unit', 'status']),
            'recentActivities' => (clone $ownedActivities)
                ->with('crop:id,name,land_id', 'crop.land:id,name')
                ->whereDate('scheduled_date', '<=', $today->toDateString())
                ->orderByDesc('scheduled_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'recentHarvests' => (clone $ownedHarvests)
                ->with('crop:id,name,land_id', 'crop.land:id,name')
                ->orderByDesc('harvest_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  array<string, float>  $totals
     */
    private function addMonthlyTotals(Collection $rows, array &$totals): void
    {
        foreach ($rows as $row) {
            $monthKey = CarbonImmutable::parse($row->period_date)->format('Y-m');

            if (array_key_exists($monthKey, $totals)) {
                $totals[$monthKey] += (float) $row->amount;
            }
        }
    }
}
