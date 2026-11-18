<?php

namespace App\TenantModules\Tenants\TanCang\PortMap\Services;

use App\Core\Tenant\Tenant;
use App\Core\Tenant\TenantDatabaseManager;
use App\TenantModules\Tenants\TanCang\PortMap\Models\MapLayer;
use App\TenantModules\Tenants\TanCang\PortMap\Models\PortLocation;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PortMapConfigService
{
    public function seedDefaultsIfEmpty(): void
    {
        if (PortLocation::query()->count() === 0) {
            foreach ($this->defaultPorts() as $index => $port) {
                PortLocation::query()->create(array_merge($port, [
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ]));
            }
        }

        if (MapLayer::query()->count() === 0) {
            foreach ($this->defaultLayers() as $index => $layer) {
                MapLayer::query()->create(array_merge($layer, [
                    'sort_order' => ($index + 1) * 10,
                ]));
            }
        }
    }

    public function forFrontend(): array
    {
        try {
            $tenant = Tenant::query()->where('slug', 'tan-cang')->first();
            if (! $tenant || ! $tenant->database) {
                return [];
            }

            app(TenantDatabaseManager::class)->connect($tenant);

            if (! Schema::connection('tenant')->hasTable('port_locations')
                || ! Schema::connection('tenant')->hasTable('map_layers')) {
                return [];
            }

            $this->seedDefaultsIfEmpty();

            $ports = PortLocation::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->mapWithKeys(function (PortLocation $port) {
                    return [
                        $port->code => [
                            'name' => $port->name,
                            'short' => $port->short_name,
                            'region' => $port->region,
                            'isHq' => $port->is_hq,
                            'hasTour' => $port->has_tour,
                            'tourUrl' => $port->tour_url,
                            'img' => $port->image_path ? asset('storage/' . ltrim($port->image_path, '/')) : null,
                            'rows' => is_array($port->popup_rows) ? array_values($port->popup_rows) : [],
                            'active' => $port->is_active,
                        ],
                    ];
                })
                ->all();

            $layers = MapLayer::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->mapWithKeys(function (MapLayer $layer) {
                    return [
                        $layer->layer_key => [
                            'name' => $layer->name,
                            'defaultEnabled' => $layer->default_enabled,
                            'visible' => $layer->is_visible,
                            'sortOrder' => $layer->sort_order,
                        ],
                    ];
                })
                ->all();

            return [
                'ports' => $ports,
                'layers' => $layers,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    private function defaultPorts(): array
    {
        return [
            [
                'code' => 'hq',
                'name' => 'Trụ sở chính Tổng công ty Tân Cảng Sài Gòn',
                'short_name' => 'Trụ sở chính',
                'region' => 'nam',
                'is_hq' => true,
                'has_tour' => false,
                'popup_rows' => [
                    ['k' => 'Địa chỉ', 'v' => '[Địa chỉ trụ sở – do TCT cung cấp]'],
                    ['k' => 'Liên hệ', 'v' => '[Điện thoại, email – do TCT cung cấp]'],
                ],
            ],
            [
                'code' => 'quevo',
                'name' => 'ICD Tân Cảng – Quế Võ',
                'short_name' => 'Quế Võ',
                'region' => 'bac',
                'is_hq' => false,
                'has_tour' => false,
                'popup_rows' => $this->defaultPortRows(),
            ],
            [
                'code' => 'haiphong',
                'name' => 'Cảng Container Quốc tế Tân Cảng – Hải Phòng (TC-HICT)',
                'short_name' => 'Hải Phòng',
                'region' => 'bac',
                'is_hq' => false,
                'has_tour' => false,
                'popup_rows' => $this->defaultPortRows(),
            ],
            [
                'code' => 'mientrung',
                'name' => 'Cảng Tân Cảng – Miền Trung',
                'short_name' => 'Miền Trung',
                'region' => 'trung',
                'is_hq' => false,
                'has_tour' => false,
                'popup_rows' => $this->defaultPortRows(),
            ],
            [
                'code' => 'camranh',
                'name' => 'Cảng Quốc tế Cam Ranh',
                'short_name' => 'Cam Ranh',
                'region' => 'trung',
                'is_hq' => false,
                'has_tour' => false,
                'popup_rows' => $this->defaultPortRows(),
            ],
            [
                'code' => 'catlai',
                'name' => 'Cảng Tân Cảng – Cát Lái',
                'short_name' => 'Cát Lái',
                'region' => 'nam',
                'is_hq' => false,
                'has_tour' => true,
                'popup_rows' => $this->defaultPortRows(),
            ],
            [
                'code' => 'tcit',
                'name' => 'Cảng Quốc tế Tân Cảng – Cái Mép (TCIT)',
                'short_name' => 'TCIT – Cái Mép',
                'region' => 'nam',
                'is_hq' => false,
                'has_tour' => true,
                'popup_rows' => $this->defaultPortRows(),
            ],
            [
                'code' => 'caolanh',
                'name' => 'Cảng Tân Cảng – Cao Lãnh',
                'short_name' => 'Cao Lãnh',
                'region' => 'nam',
                'is_hq' => false,
                'has_tour' => false,
                'popup_rows' => $this->defaultPortRows(),
            ],
        ];
    }

    private function defaultPortRows(): array
    {
        return [
            ['k' => 'Vị trí', 'v' => '[Địa chỉ cảng – do TCT cung cấp]'],
            ['k' => 'Diện tích', 'v' => '[… ha – do TCT cung cấp]'],
            ['k' => 'Năng lực', 'v' => '[… TEU/năm – do TCT cung cấp]'],
        ];
    }

    private function defaultLayers(): array
    {
        return [
            ['layer_key' => 'dn', 'name' => 'Ngày / Đêm', 'default_enabled' => false, 'is_visible' => true],
            ['layer_key' => 'dom', 'name' => 'Tuyến nội địa', 'default_enabled' => false, 'is_visible' => true],
            ['layer_key' => 'intl', 'name' => 'Tuyến quốc tế', 'default_enabled' => true, 'is_visible' => true],
            ['layer_key' => 'air', 'name' => 'Sân bay', 'default_enabled' => false, 'is_visible' => true],
            ['layer_key' => 'port', 'name' => 'Cảng biển', 'default_enabled' => false, 'is_visible' => true],
            ['layer_key' => 'city', 'name' => 'Đô thị', 'default_enabled' => false, 'is_visible' => true],
        ];
    }
}
