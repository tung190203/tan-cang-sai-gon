<?php

namespace Tests\Unit;

use App\Core\Tenant\Tenant;
use App\Http\Controllers\Backend\ProfileController;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\PresenceVerifierInterface;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_ckfinder_disallows_svg_and_enforces_upload_limits(): void
    {
        $resourceTypes = collect(config('ckfinder.resourceTypes', []))->keyBy('name');

        $images = $resourceTypes->get('Images');
        $files = $resourceTypes->get('Files');
        $videos = $resourceTypes->get('Videos');

        $this->assertNotNull($images);
        $this->assertStringNotContainsString('svg', strtolower((string) $images['allowedExtensions']));
        $this->assertGreaterThan(0, (int) $images['maxSize']);
        $this->assertGreaterThan(0, (int) $files['maxSize']);
        $this->assertGreaterThan(0, (int) $videos['maxSize']);
        $this->assertTrue((bool) config('ckfinder.disallowUnsafeCharacters'));
    }

    public function test_ensure_module_enabled_blocks_cross_tenant_route_parameter(): void
    {
        $tenant = new Tenant(['slug' => 'tan-cang']);
        $tenant->id = 1;

        $user = new User();
        $user->setRelation('tenant', $tenant);

        $request = Request::create('/backend/tenants/other-tenant/modules/dashboard', 'GET');
        $route = new Route('GET', 'backend/tenants/{tenant}/modules/dashboard', fn () => 'ok');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureModuleEnabled();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Tenant không hợp lệ.');

        $middleware->handle($request, fn () => response('ok'), 'dashboard');
    }

    public function test_profile_controller_rejects_php_avatar_upload(): void
    {
        $this->mockPresenceVerifier();

        $user = new User(['name' => 'Tester', 'email' => 'tester@example.com']);
        $user->id = 1;

        Auth::shouldReceive('guard')->with('web')->andReturnSelf();
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/backend/profile/update', 'POST', [
            'name' => 'Tester',
            'email' => 'tester@example.com',
        ], [], [
            'avatar_file' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ]);

        $controller = new ProfileController();

        $this->expectException(ValidationException::class);
        $controller->update($request);
    }

    public function test_profile_controller_rejects_svg_base64_avatar(): void
    {
        $this->mockPresenceVerifier();

        $user = new User(['name' => 'Tester', 'email' => 'tester@example.com']);
        $user->id = 1;

        Auth::shouldReceive('guard')->with('web')->andReturnSelf();
        Auth::shouldReceive('user')->andReturn($user);

        $svgPayload = 'data:image/svg;base64,' . base64_encode('<svg onload="alert(1)"></svg>');
        $request = Request::create('/backend/profile/update', 'POST', [
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'avatar' => $svgPayload,
        ]);

        $controller = new ProfileController();
        $response = $controller->update($request);

        $this->assertSame(422, $response->getStatusCode());
    }

    private function mockPresenceVerifier(): void
    {
        $verifier = new class implements PresenceVerifierInterface {
            public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null, array $extra = [])
            {
                return 0;
            }

            public function getMultiCount($collection, $column, array $values, array $extra = [])
            {
                return 0;
            }
        };

        $this->app->make('validator')->setPresenceVerifier($verifier);
    }
}
