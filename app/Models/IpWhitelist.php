<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class IpWhitelist extends Model
{
    public const CACHE_KEY = 'security:ip_whitelist:active_ips';

    protected $fillable = [
        'ip_address',
        'label',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all active whitelisted IP addresses/CIDRs.
     *
     * @return array<int, string>
     */
    public static function activeIps(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            return self::query()
                ->where('is_active', true)
                ->pluck('ip_address')
                ->all();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function isValidIpOrCidr(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if (! str_contains($value, '/')) {
            return false;
        }

        [$subnet, $mask] = explode('/', $value, 2);
        if (! ctype_digit($mask)) {
            return false;
        }

        $maskInt = (int) $mask;
        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return $maskInt >= 0 && $maskInt <= 32;
        }

        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return $maskInt >= 0 && $maskInt <= 128;
        }

        return false;
    }
}
