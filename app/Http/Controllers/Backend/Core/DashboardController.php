<?php

namespace App\Http\Controllers\Backend\Core;

use App\Core\Module\Module;
use App\Core\Permission\Permission;
use App\Core\Permission\Role;
use App\Core\Tenant\Tenant;
use App\Http\Controllers\Controller;
use App\Core\Tenant\TenantDatabaseManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    protected string $selectedMainMenu = 'dashboard';

    public function index(Request $request)
    {
        if (! $request->user()->isPlatformOwner()) {
            $tenant = $request->user()->tenant;

            abort_unless($tenant, 403, 'User chưa được gắn tenant.');

            app(TenantDatabaseManager::class)->connect($tenant);

            abort_unless(
                Module::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('slug', 'dashboard')
                    ->where('is_enabled', true)
                    ->exists(),
                403,
                'Module chưa khả dụng'
            );

            $controllerClass = $this->resolveTenantDashboardController($tenant->slug);
            if ($controllerClass !== null) {
                return app($controllerClass)->index($request);
            }

            return view('backend.core.tenant-dashboard', compact('tenant'));
        }

        return view('backend.core.dashboard', [
            'tenantCount' => Tenant::count(),
            'activeTenantCount' => Tenant::where('status', Tenant::STATUS_ACTIVE)->count(),
            'moduleCount' => Module::count(),
            'enabledModuleCount' => Module::where('is_enabled', true)->count(),
            'userCount' => User::count(),
            'roleCount' => Role::count(),
            'permissionCount' => Permission::count(),
            'recentTenants' => Tenant::withCount(['modules', 'users'])->latest()->limit(5)->get(),
        ]);
    }

    public function exportLogs(Request $request)
    {
        if (! $request->user()->isPlatformOwner()) {
            $tenant = $request->user()->tenant;

            abort_unless($tenant, 403, 'User chưa được gắn tenant.');

            app(TenantDatabaseManager::class)->connect($tenant);

            abort_unless(
                Module::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('slug', 'dashboard')
                    ->where('is_enabled', true)
                    ->exists(),
                403,
                'Module chưa khả dụng'
            );

            abort_unless(
                $request->user()->hasPermission('dashboard') || $request->user()->hasPermission('dashboard/view'),
                403,
                'Quyền hạn không đủ.'
            );

            $controllerClass = $this->resolveTenantDashboardController($tenant->slug);
            if ($controllerClass !== null && method_exists($controllerClass, 'exportLogs')) {
                return app($controllerClass)->exportLogs($request);
            }

            abort(404);
        }

        abort(404);
    }

    private function resolveTenantDashboardController(string $tenantSlug): ?string
    {
        $tenantStudly = Str::studly($tenantSlug);
        $candidate = "App\\TenantModules\\Tenants\\{$tenantStudly}\\Dashboard\\Http\\Controllers\\DashboardController";

        return class_exists($candidate) ? $candidate : null;
    }
}
