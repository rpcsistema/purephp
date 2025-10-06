<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Super Admin routes (central domain only)
Route::middleware(['super.admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('tenants', SuperAdminController::class);
    Route::post('/tenants/{tenant}/toggle-status', [SuperAdminController::class, 'toggleStatus'])->name('tenants.toggle-status');
});

// Tenant routes (require tenant context)
Route::middleware(['auth', 'tenant'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Financial Module Routes
    Route::prefix('financial')->name('financial.')->group(function () {
        
        // Financial Dashboard
        Route::get('/', [FinancialController::class, 'index'])->name('dashboard');
        
        // Reports
        Route::get('/reports', [FinancialController::class, 'reports'])->name('reports');
        
        // Transactions
        Route::resource('transactions', TransactionController::class);
        
        // Categories
        Route::resource('categories', CategoryController::class);
        Route::get('/categories-by-type', [CategoryController::class, 'getByType'])->name('categories.by-type');
        
        // Budgets
        Route::resource('budgets', BudgetController::class);
        Route::post('/budgets/{budget}/update-spent', [BudgetController::class, 'updateSpent'])->name('budgets.update-spent');
    });

    // User Management (Admin only)
    Route::middleware('can:users.view')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('can:users.create');
        Route::post('/', [UserController::class, 'store'])->name('store')->middleware('can:users.create');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('can:users.edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('can:users.edit');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('can:users.delete');
    });

    // Settings
    Route::middleware('can:settings.view')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/general', [SettingsController::class, 'updateGeneral'])->name('update.general')->middleware('can:settings.edit');
        Route::put('/white-label', [SettingsController::class, 'updateWhiteLabel'])->name('update.white-label')->middleware('can:settings.edit');
    });

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('update.password');
    });
});