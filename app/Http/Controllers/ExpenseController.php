<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Crop;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $expenses = $request->user()
            ->expenses()
            ->with(['crop.land:id,name', 'product:id,name'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('expenses.index', ['expenses' => $expenses]);
    }

    public function create(Request $request): View
    {
        return view('expenses.form', [
            'expense' => new Expense(['expense_date' => today()->toDateString()]),
            ...$this->formOptions($request->user()),
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $request->user()->expenses()->create($request->validated());

        return to_route('expenses.index')->with('status', 'Pengeluaran berhasil ditambahkan.');
    }

    public function edit(Request $request, Expense $expense): View
    {
        $expense = $request->user()->expenses()->findOrFail($expense->getKey());

        return view('expenses.form', [
            'expense' => $expense,
            ...$this->formOptions($request->user()),
        ]);
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense = $request->user()->expenses()->findOrFail($expense->getKey());
        $expense->update($request->validated());

        return to_route('expenses.index')->with('status', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $expense = $request->user()->expenses()->findOrFail($expense->getKey());
        $expense->delete();

        return to_route('expenses.index')->with('status', 'Pengeluaran berhasil dihapus.');
    }

    /** @return array{crops: Collection<int, Crop>, products: Collection<int, Product>} */
    private function formOptions(User $user): array
    {
        return [
            'crops' => Crop::query()
                ->whereHas('land', fn (Builder $query) => $query->where('user_id', $user->getKey()))
                ->with('land:id,name')
                ->orderBy('name')
                ->get(),
            'products' => $user->products()->orderBy('name')->get(),
        ];
    }
}
