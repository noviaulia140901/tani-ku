<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Land;
use App\Models\Crop;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'totalLands' => Land::count(),
            'totalCrops' => Crop::count(),
            'totalProducts' => Product::count(),
        ]);
    }
}
