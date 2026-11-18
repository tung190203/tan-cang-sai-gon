<?php

use App\TenantModules\Tenants\TanCang\PortMap\Services\PortMapConfigService;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';
require __DIR__ . '/backend.php';

Route::localized(function () {
    Route::get('/', function (PortMapConfigService $portMapConfig) {
        $mapPath = base_path('index.html');

        if (is_file($mapPath)) {
            $html = file_get_contents($mapPath);
            $config = $portMapConfig->forFrontend();
            $encoded = json_encode(
                $config,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
            );
            $inject = '<script>window.__TANCANG_CONFIG__=' . ($encoded ?: '{}') . ';</script></head>';
            $html = str_replace('</head>', $inject, $html);

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return redirect()->route('backend_dashboard');
    })->name('home');
});
