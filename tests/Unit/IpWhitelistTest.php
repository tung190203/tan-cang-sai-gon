<?php

namespace Tests\Unit;

use App\Http\Middleware\IpWhitelistMiddleware;
use App\Models\IpWhitelist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IpWhitelistTest extends TestCase
{
    public function test_validates_ip_and_cidr_formats(): void
    {
        $this->assertTrue(IpWhitelist::isValidIpOrCidr('127.0.0.1'));
        $this->assertTrue(IpWhitelist::isValidIpOrCidr('::1'));
        $this->assertTrue(IpWhitelist::isValidIpOrCidr('192.168.1.57'));
        $this->assertTrue(IpWhitelist::isValidIpOrCidr('192.168.1.0/24'));
        $this->assertTrue(IpWhitelist::isValidIpOrCidr('2001:db8::/32'));

        $this->assertFalse(IpWhitelist::isValidIpOrCidr('999.999.999.999'));
        $this->assertFalse(IpWhitelist::isValidIpOrCidr('192.168.1.0/33'));
        $this->assertFalse(IpWhitelist::isValidIpOrCidr('not-an-ip'));
    }

    public function test_middleware_allows_whitelisted_ip_and_cidr(): void
    {
        config(['security.ip_whitelist.enabled' => true]);
        Schema::shouldReceive('hasTable')->with('ip_whitelists')->andReturn(true);
        Cache::put(IpWhitelist::CACHE_KEY, ['127.0.0.1', '192.168.1.0/24'], 300);

        $middleware = new IpWhitelistMiddleware();

        $allowedLocal = Request::create('/login', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $resLocal = $middleware->handle($allowedLocal, fn () => response('ok', 200));
        $this->assertSame(200, $resLocal->getStatusCode());

        $allowedCidr = Request::create('/login', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.1.57']);
        $resCidr = $middleware->handle($allowedCidr, fn () => response('ok', 200));
        $this->assertSame(200, $resCidr->getStatusCode());
    }

    public function test_middleware_blocks_non_whitelisted_ip(): void
    {
        config(['security.ip_whitelist.enabled' => true]);
        Schema::shouldReceive('hasTable')->with('ip_whitelists')->andReturn(true);
        Cache::put(IpWhitelist::CACHE_KEY, ['127.0.0.1'], 300);

        $middleware = new IpWhitelistMiddleware();
        $blocked = Request::create('/login', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.10']);
        $resBlocked = $middleware->handle($blocked, fn () => response('ok', 200));

        $this->assertSame(403, $resBlocked->getStatusCode());
        $this->assertStringContainsString('Truy cập bị từ chối', (string) $resBlocked->getContent());
        $this->assertStringNotContainsString('203.0.113.10', (string) $resBlocked->getContent());
    }

    public function test_middleware_passes_through_when_disabled(): void
    {
        config(['security.ip_whitelist.enabled' => false]);

        $middleware = new IpWhitelistMiddleware();
        $request = Request::create('/login', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.10']);
        $res = $middleware->handle($request, fn () => response('ok', 200));

        $this->assertSame(200, $res->getStatusCode());
    }
}
