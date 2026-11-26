<?php

namespace Tests\Feature;

use Tests\TestCase;

class BasicAuthTest extends TestCase
{
    public function test_requests_are_allowed_when_basic_auth_is_disabled(): void
    {
        config(['security.basic_auth.enabled' => false]);

        $this->get('/login')->assertOk();
    }

    public function test_requests_require_credentials_when_basic_auth_is_enabled(): void
    {
        config([
            'security.basic_auth.enabled' => true,
            'security.basic_auth.username' => 'tancang_admin',
            'security.basic_auth.password' => 'Tc@2026#StrongPass!9',
            'security.basic_auth.realm' => 'Tan Cang',
        ]);

        $response = $this->get('/login');

        $response->assertStatus(401);
        $response->assertHeader('WWW-Authenticate', 'Basic realm="Tan Cang", charset="UTF-8"');
    }

    public function test_requests_with_invalid_credentials_are_rejected(): void
    {
        config([
            'security.basic_auth.enabled' => true,
            'security.basic_auth.username' => 'tancang_admin',
            'security.basic_auth.password' => 'Tc@2026#StrongPass!9',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('tancang_admin:wrong-pass'),
        ])->get('/login');

        $response->assertStatus(401);
    }

    public function test_requests_with_valid_credentials_are_allowed(): void
    {
        config([
            'security.basic_auth.enabled' => true,
            'security.basic_auth.username' => 'tancang_admin',
            'security.basic_auth.password' => 'Tc@2026#StrongPass!9',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('tancang_admin:Tc@2026#StrongPass!9'),
        ])->get('/login');

        $response->assertOk();
    }

    public function test_empty_configured_credentials_fail_closed(): void
    {
        config([
            'security.basic_auth.enabled' => true,
            'security.basic_auth.username' => '',
            'security.basic_auth.password' => '',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode(':'),
        ])->get('/login');

        $response->assertStatus(401);
    }
}
