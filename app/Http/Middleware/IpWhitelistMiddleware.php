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
            return $this->forbiddenResponse();
        }

        $allowedIps = IpWhitelist::activeIps();

        if ($allowedIps === [] || ! IpUtils::checkIp($clientIp, $allowedIps)) {
            return $this->forbiddenResponse();
        }

        return $next($request);
    }

    private function forbiddenResponse(): Response
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Truy cập bị từ chối</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f4f6f9;
            color: #1f2937;
            text-align: center;
        }
        .box {
            max-width: 520px;
            background: #ffffff;
            padding: 32px 28px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        h1 {
            font-size: 20px;
            font-weight: 600;
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>Truy cập bị từ chối</h1>
    </div>
</body>
</html>
HTML;

        return response($html, 403, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
