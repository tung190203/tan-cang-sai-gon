<?php

namespace App\Http\Controllers\Backend\Core;

use App\Http\Controllers\Controller;
use App\Models\IpWhitelist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\IpUtils;

class IpWhitelistController extends Controller
{
    protected string $selectedMainMenu = 'ip_whitelist';

    public function index(Request $request): View
    {
        $entries = IpWhitelist::query()
            ->with('creator:id,name,email')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        return view('backend.core.ip-whitelist.index', [
            'entries' => $entries,
            'currentIp' => (string) $request->ip(),
            'isEnabled' => (bool) config('security.ip_whitelist.enabled', false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $ipAddress = trim((string) $request->input('ip_address'));
        $request->merge(['ip_address' => $ipAddress]);

        $validated = $request->validate([
            'ip_address' => [
                'required',
                'string',
                'max:45',
                'unique:ip_whitelists,ip_address',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! IpWhitelist::isValidIpOrCidr((string) $value)) {
                        $fail('Địa chỉ IP hoặc CIDR không hợp lệ (ví dụ: 192.168.1.10 hoặc 192.168.1.0/24).');
                    }
                },
            ],
            'label' => ['nullable', 'string', 'max:255'],
        ], [
            'ip_address.required' => 'Vui lòng nhập địa chỉ IP.',
            'ip_address.unique' => 'Địa chỉ IP này đã tồn tại trong whitelist.',
        ]);

        IpWhitelist::create([
            'ip_address' => $validated['ip_address'],
            'label' => isset($validated['label']) ? trim($validated['label']) : null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        IpWhitelist::clearCache();

        return redirect()
            ->route('backend_core_ip_whitelist')
            ->with('success', 'Đã thêm IP ' . $validated['ip_address'] . ' vào whitelist.');
    }

    public function toggle(Request $request, IpWhitelist $ipWhitelist): RedirectResponse
    {
        $newState = ! $ipWhitelist->is_active;

        if (! $newState && $this->wouldLockOutCurrentClient($request, $ipWhitelist)) {
            return redirect()
                ->route('backend_core_ip_whitelist')
                ->with('error', 'Không thể tắt IP hiện tại của bạn khi không có quy tắc active nào khác khớp với IP ' . $request->ip() . '.');
        }

        $ipWhitelist->update(['is_active' => $newState]);
        IpWhitelist::clearCache();

        return redirect()
            ->route('backend_core_ip_whitelist')
            ->with('success', ($newState ? 'Đã bật' : 'Đã tắt') . ' IP ' . $ipWhitelist->ip_address . '.');
    }

    public function destroy(Request $request, IpWhitelist $ipWhitelist): RedirectResponse
    {
        if ($ipWhitelist->is_active && $this->wouldLockOutCurrentClient($request, $ipWhitelist)) {
            return redirect()
                ->route('backend_core_ip_whitelist')
                ->with('error', 'Không thể xóa IP hiện tại của bạn khi không có quy tắc active nào khác khớp với IP ' . $request->ip() . '.');
        }

        $deletedIp = $ipWhitelist->ip_address;
        $ipWhitelist->delete();
        IpWhitelist::clearCache();

        return redirect()
            ->route('backend_core_ip_whitelist')
            ->with('success', 'Đã xóa IP ' . $deletedIp . ' khỏi whitelist.');
    }

    private function wouldLockOutCurrentClient(Request $request, IpWhitelist $target): bool
    {
        if (! config('security.ip_whitelist.enabled', false)) {
            return false;
        }

        $clientIp = (string) $request->ip();
        if ($clientIp === '') {
            return false;
        }

        $remainingActiveIps = IpWhitelist::query()
            ->where('is_active', true)
            ->where('id', '!=', $target->id)
            ->pluck('ip_address')
            ->all();

        return $remainingActiveIps === [] || ! IpUtils::checkIp($clientIp, $remainingActiveIps);
    }
}
