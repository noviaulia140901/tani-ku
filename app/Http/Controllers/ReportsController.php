<?php

namespace App\Http\Controllers;

use App\Models\Harvest;
use App\Models\Sale;
use App\Services\ReportExportService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('reports.index', $this->reportData($request));
    }

    public function export(Request $request, string $format, ReportExportService $exportService): Response
    {
        $report = $this->reportData($request);
        $content = match ($format) {
            'pdf' => $exportService->pdf($report),
            'xls' => $exportService->excel($report),
            default => abort(404),
        };
        $mimeType = $format === 'pdf' ? 'application/pdf' : 'application/vnd.ms-excel; charset=UTF-8';

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="laporan-taniku-'.$report['from'].'-'.$report['to'].'.'.$format.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     totalIncome: float,
     *     totalExpenses: float,
     *     netBalance: float,
     *     harvestCount: int,
     *     harvestSummary: Collection<int, object>,
     *     financialChart: array{labels: array<int, string>, income: array<int, float>, expenses: array<int, float>},
     *     hasFinancialData: bool
     * }
     */
    private function reportData(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], [
            'from.date_format' => 'Tanggal awal harus menggunakan format yang valid.',
            'to.date_format' => 'Tanggal akhir harus menggunakan format yang valid.',
            'to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        $from = $validated['from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? now()->toDateString();
        $userId = $request->user()->getAuthIdentifier();
        $ownedSales = Sale::query()
            ->whereHas('harvest.crop.land', fn (Builder $query) => $query->where('user_id', $userId))
            ->whereDate('sale_date', '>=', $from)
            ->whereDate('sale_date', '<=', $to);
        $ownedExpenses = $request->user()->expenses()
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to);
        $ownedHarvests = Harvest::query()
            ->whereHas('crop.land', fn (Builder $query) => $query->where('user_id', $userId))
            ->whereDate('harvest_date', '>=', $from)
            ->whereDate('harvest_date', '<=', $to);
        $totalIncome = (float) (clone $ownedSales)->sum('total');
        $totalExpenses = (float) (clone $ownedExpenses)->sum('amount');
        $monthlyIncome = [];
        $monthlyExpenses = [];
        $firstMonth = CarbonImmutable::parse($from)->startOfMonth();
        $lastMonth = CarbonImmutable::parse($to)->startOfMonth();

        for ($month = $firstMonth; $month <= $lastMonth; $month = $month->addMonth()) {
            $monthKey = $month->format('Y-m');
            $monthlyIncome[$monthKey] = 0.0;
            $monthlyExpenses[$monthKey] = 0.0;
        }

        $incomeRows = (clone $ownedSales)
            ->selectRaw('sale_date as transaction_date, SUM(total) as amount')
            ->groupBy('sale_date')
            ->get();
        $expenseRows = (clone $ownedExpenses)
            ->selectRaw('expense_date as transaction_date, SUM(amount) as amount')
            ->groupBy('expense_date')
            ->get();

        foreach ($incomeRows as $row) {
            $monthKey = CarbonImmutable::parse($row->transaction_date)->format('Y-m');
            $monthlyIncome[$monthKey] += (float) $row->amount;
        }

        foreach ($expenseRows as $row) {
            $monthKey = CarbonImmutable::parse($row->transaction_date)->format('Y-m');
            $monthlyExpenses[$monthKey] += (float) $row->amount;
        }

        $harvestSummary = (clone $ownedHarvests)
            ->selectRaw('unit, SUM(quantity) as quantity')
            ->groupBy('unit')
            ->orderBy('unit')
            ->get();

        return [
            'from' => $from,
            'to' => $to,
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'netBalance' => $totalIncome - $totalExpenses,
            'harvestCount' => (clone $ownedHarvests)->count(),
            'harvestSummary' => $harvestSummary,
            'financialChart' => [
                'labels' => collect(array_keys($monthlyIncome))
                    ->map(fn (string $monthKey): string => CarbonImmutable::createFromFormat('!Y-m', $monthKey)
                        ->locale('id')
                        ->translatedFormat('M Y'))
                    ->all(),
                'income' => array_values($monthlyIncome),
                'expenses' => array_values($monthlyExpenses),
            ],
            'hasFinancialData' => (clone $ownedSales)->exists() || (clone $ownedExpenses)->exists(),
        ];
    }
}
