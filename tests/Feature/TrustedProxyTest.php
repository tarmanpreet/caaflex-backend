<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_https_forwarded_by_the_docker_proxy_is_detected(): void
    {
        Route::get('/trusted-proxy-test', function (Request $request): array {
            return [
                'secure' => $request->secure(),
                'url' => url('/dashboard'),
            ];
        });

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '172.18.0.2'])
            ->withHeaders([
                'X-Forwarded-Host' => 'caf.example.com',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/trusted-proxy-test');

        $response->assertOk()->assertExactJson([
            'secure' => true,
            'url' => 'https://caf.example.com/dashboard',
        ]);
    }
}
