<?php

namespace App\TenantModules\Tenants\TanCang\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected string $selectedMainMenu = 'dashboard';

    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user?->isPlatformOwner()
            || $user?->isTenantOrganizer()
            || $user?->hasPermission('dashboard.view')
            || $user?->hasPermission('dashboard/view'),
            403,
            self::MESSAGE_UNAUTHORIZED
        );

        return view('tenant-tan-cang-dashboard::index', [
            'tenant' => $user->tenant,
        ]);
    }
}
