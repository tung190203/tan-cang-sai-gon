<?php

namespace App\TenantModules\Tenants\TanCang\PortMap\Models;

use Illuminate\Database\Eloquent\Model;

class PortLocation extends Model
{
    protected $connection = 'tenant';

    protected $table = 'port_locations';

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'short_name',
        'short_name_en',
        'region',
        'is_hq',
        'has_tour',
        'tour_url',
        'image_path',
        'popup_rows',
        'popup_rows_en',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_hq' => 'boolean',
        'has_tour' => 'boolean',
        'popup_rows' => 'array',
        'popup_rows_en' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
