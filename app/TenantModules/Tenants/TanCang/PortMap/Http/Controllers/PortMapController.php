<?php

namespace App\TenantModules\Tenants\TanCang\PortMap\Http\Controllers;

use App\Http\Controllers\Controller;
use App\TenantModules\Tenants\TanCang\PortMap\Models\MapLayer;
use App\TenantModules\Tenants\TanCang\PortMap\Models\PortLocation;
use App\TenantModules\Tenants\TanCang\PortMap\Services\PortMapConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PortMapController extends Controller
{
    private const ALLOWED_IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];

    protected string $selectedMainMenu = 'port-map';

    public function __construct(private readonly PortMapConfigService $configService)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $this->authorizeModule($request, 'view');
        $this->configService->seedDefaultsIfEmpty();

        return view('tenant-tan-cang-port-map::index', [
            'ports' => PortLocation::query()->orderBy('sort_order')->orderBy('id')->get(),
            'layers' => MapLayer::query()->orderBy('sort_order')->orderBy('id')->get(),
            'canUpdate' => $this->canModule($request, 'update'),
        ]);
    }

    public function editPort(Request $request, PortLocation $port)
    {
        $this->authorizeModule($request, 'update');

        return view('tenant-tan-cang-port-map::edit-port', [
            'port' => $port,
            'regions' => [
                'bac' => 'Miền Bắc',
                'trung' => 'Miền Trung',
                'nam' => 'Miền Nam',
            ],
        ]);
    }

    public function updatePort(Request $request, PortLocation $port)
    {
        $this->authorizeModule($request, 'update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:100'],
            'region' => ['required', Rule::in(['bac', 'trung', 'nam'])],
            'has_tour' => ['nullable', 'boolean'],
            'tour_url' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^(https?:\/\/|\/)[^\s<>"\']*$/i',
            ],
            'is_active' => ['nullable', 'boolean'],
            'remove_image' => ['nullable', 'boolean'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'popup_keys' => ['nullable', 'array', 'max:20'],
            'popup_keys.*' => ['nullable', 'string', 'max:100'],
            'popup_values' => ['nullable', 'array', 'max:20'],
            'popup_values.*' => ['nullable', 'string', 'max:500'],
        ], [
            'tour_url.regex' => 'Link VR Tour chỉ chấp nhận đường dẫn http://, https:// hoặc đường dẫn nội bộ bắt đầu bằng /.',
        ]);

        $popupRows = [];
        $keys = $validated['popup_keys'] ?? [];
        $values = $validated['popup_values'] ?? [];
        foreach ($keys as $index => $key) {
            $cleanKey = trim((string) $key);
            $cleanVal = trim((string) ($values[$index] ?? ''));
            if ($cleanKey !== '' || $cleanVal !== '') {
                $popupRows[] = [
                    'k' => $cleanKey,
                    'v' => $cleanVal,
                ];
            }
        }

        if ($request->boolean('remove_image') && $port->image_path) {
            if (Storage::disk('public')->exists($port->image_path)) {
                Storage::disk('public')->delete($port->image_path);
            }
            $port->image_path = null;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = strtolower($file->guessExtension() ?: $file->extension());
            if (! in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
                return back()->withErrors(['image' => 'Định dạng ảnh không hợp lệ.'])->withInput();
            }

            if ($port->image_path && Storage::disk('public')->exists($port->image_path)) {
                Storage::disk('public')->delete($port->image_path);
            }

            $fileName = 'port-map/' . Str::random(40) . '.' . $extension;
            Storage::disk('public')->putFileAs('port-map', $file, basename($fileName));
            $port->image_path = $fileName;
        }

        $port->fill([
            'name' => trim(strip_tags($validated['name'])),
            'short_name' => trim(strip_tags($validated['short_name'])),
            'region' => $validated['region'],
            'has_tour' => $request->boolean('has_tour'),
            'tour_url' => $validated['tour_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'popup_rows' => $popupRows,
        ])->save();

        return redirect()
            ->route('tenant.tan_cang.port_map.index')
            ->with('success', 'Đã cập nhật thông tin popup cảng: ' . $port->short_name);
    }

    public function togglePort(Request $request, PortLocation $port)
    {
        $this->authorizeModule($request, 'update');

        $port->forceFill(['is_active' => ! $port->is_active])->save();

        return back()->with('success', 'Đã cập nhật trạng thái hiển thị cảng.');
    }

    public function updateLayers(Request $request)
    {
        $this->authorizeModule($request, 'update');

        $validated = $request->validate([
            'layers' => ['required', 'array'],
            'layers.*.name' => ['required', 'string', 'max:120'],
            'layers.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'layers.*.default_enabled' => ['nullable', 'boolean'],
            'layers.*.is_visible' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['layers'] as $layerId => $layerData) {
            $layer = MapLayer::query()->find((int) $layerId);
            if (! $layer) {
                continue;
            }

            $layer->update([
                'name' => trim(strip_tags($layerData['name'])),
                'sort_order' => (int) $layerData['sort_order'],
                'default_enabled' => ! empty($layerData['default_enabled']),
                'is_visible' => ! empty($layerData['is_visible']),
            ]);
        }

        return redirect()
            ->route('tenant.tan_cang.port_map.index', ['tab' => 'layers'])
            ->with('success', 'Đã cập nhật cấu hình các lớp bản đồ.');
    }

    private function authorizeModule(Request $request, string $permission): void
    {
        abort_unless($this->canModule($request, $permission), 403, self::MESSAGE_UNAUTHORIZED);
    }

    private function canModule(Request $request, string $permission): bool
    {
        $user = $request->user();

        return (bool) (
            $user?->isPlatformOwner()
            || $user?->isTenantOrganizer()
            || $user?->hasPermission('port-map.' . $permission)
            || $user?->hasPermission('port-map/' . $permission)
        );
    }
}
