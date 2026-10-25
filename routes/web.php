<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\CropController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardLocationController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HarvestController;
use App\Http\Controllers\LandController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SaleController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', PreventBackHistory::class])
    ->name('dashboard');

// Dashboard khusus admin.
Route::get('/admin', AdminDashboardController::class)
    ->middleware(['auth', 'admin', PreventBackHistory::class])
    ->name('admin.dashboard');

// Pengelolaan akun khusus admin.
Route::middleware(['auth', 'admin', PreventBackHistory::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])
            ->name('users.index');

        Route::get('/users/create', [AdminUserController::class, 'create'])
            ->name('users.create');

        Route::post('/users', [AdminUserController::class, 'store'])
            ->name('users.store');
    });

// Semua halaman yang memerlukan login.
Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::post('/dashboard/location-data', DashboardLocationController::class)
        ->middleware('verified')
        ->middleware('throttle:6,1')
        ->name('dashboard.location-data');

    Route::get('/reports/export/{format}', [ReportsController::class, 'export'])
        ->whereIn('format', ['pdf', 'xls'])
        ->name('reports.export');

    Route::get('/reports', ReportsController::class)
        ->name('reports.index');

    Route::get('/activities/calendar', [ActivityController::class, 'calendar'])
        ->name('activities.calendar');

    Route::resource('activities', ActivityController::class)->except(['show']);
    Route::resource('crops', CropController::class)->except(['show']);
    Route::resource('expenses', ExpenseController::class)->except(['show']);
    Route::resource('harvests', HarvestController::class)->except(['show']);
    Route::resource('lands', LandController::class)->except(['show']);
    Route::resource('notes', NoteController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('sales', SaleController::class)->except(['show']);

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__.'/auth.php';
