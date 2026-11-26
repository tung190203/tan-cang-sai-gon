<?php

namespace App\Http\Middleware;

use App\Models\IpWhitelist;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class IpWhitelistMiddleware
{
    /**
     * Allow access only to whitelisted IPs when IP whitelist is enabled.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.ip_whitelist.enabled', false)) {
            return $next($request);
        }

        foreach (config('security.ip_whitelist.except', []) as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $clientIp = (string) $request->ip();

        if ($clientIp === '' || ! Schema::hasTable('ip_whitelists')) {
            return $this->forbiddenResponse($clientIp);
        }

        $allowedIps = IpWhitelist::activeIps();

        if ($allowedIps === [] || ! IpUtils::checkIp($clientIp, $allowedIps)) {
            return $this->forbiddenResponse($clientIp);
        }

        return $next($request);
    }

    private function forbiddenResponse(string $clientIp): Response
    {
        return response(
            'Truy cập bị từ chối. IP (' . e($clientIp) . ') không nằm trong danh sách whitelist.',
            403,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }
}
