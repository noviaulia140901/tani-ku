<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = $request->user()
            ->products()
            ->orderBy('name')
            ->paginate(10);

        return view('products.index', ['products' => $products]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product([
                'stock' => 0,
                'minimum_stock' => 0,
                'purchase_price' => 0,
            ]),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $request->user()->products()->create($request->validated());

        return to_route('products.index')->with('status', 'Produk berhasil ditambahkan.');
    }

    public function edit(Request $request, Product $product): View
    {
        $product = $request->user()->products()->findOrFail($product->getKey());

        return view('products.form', ['product' => $product]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product = $request->user()->products()->findOrFail($product->getKey());
        $product->update($request->validated());

        return to_route('products.index')->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $canDelete = DB::transaction(function () use ($request, $product): bool {
            $product = $request->user()
                ->products()
                ->lockForUpdate()
                ->findOrFail($product->getKey());

            if ($product->expenses()->exists()) {
                return false;
            }

            $product->delete();

            return true;
        });

        if (! $canDelete) {
            return to_route('products.index')
                ->with('error', 'Produk tidak dapat dihapus karena masih digunakan pada catatan pengeluaran.');
        }

        return to_route('products.index')->with('status', 'Produk berhasil dihapus.');
    }
}
