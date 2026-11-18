<?php

use App\TenantModules\Tenants\TanCang\Dashboard\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'active.user',
    'tenant.db',
    'module.enabled:dashboard,Tenants/TanCang/Dashboard',
])
    ->prefix('backend/tenants/tan-cang/modules/dashboard')
    ->name('tenant.tan_cang.dashboard.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
    });
