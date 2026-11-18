<?php

namespace App\TenantModules\Tenants\TanCang\PortMap\Models;

use Illuminate\Database\Eloquent\Model;

class MapLayer extends Model
{
    protected $connection = 'tenant';

    protected $table = 'map_layers';

    protected $fillable = [
        'layer_key',
        'name',
        'default_enabled',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'default_enabled' => 'boolean',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];
}
