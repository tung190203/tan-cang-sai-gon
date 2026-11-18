<?php

use App\TenantModules\Tenants\TanCang\PortMap\Http\Controllers\PortMapController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'active.user',
    'tenant.db',
    'module.enabled:port-map,Tenants/TanCang/PortMap',
])
    ->prefix('backend/tenants/tan-cang/modules/port-map')
    ->name('tenant.tan_cang.port_map.')
    ->group(function () {
        Route::get('/', [PortMapController::class, 'index'])->name('index');
        Route::get('/ports/{port}/edit', [PortMapController::class, 'editPort'])->name('ports.edit');
        Route::put('/ports/{port}', [PortMapController::class, 'updatePort'])->name('ports.update');
        Route::post('/ports/{port}/toggle', [PortMapController::class, 'togglePort'])->name('ports.toggle');
        Route::put('/layers', [PortMapController::class, 'updateLayers'])->name('layers.update');
    });
