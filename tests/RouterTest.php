<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\App;
use App\Support\Request;
use App\Support\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require routes_path('web.php');
    }

    public function testUrlGenerationForNamedRoute(): void
    {
        $url = Router::url('home');

        $this->assertSame('http://localhost/SeneBridge/public', $url);
    }

    public function testUrlGenerationWithParams(): void
    {
        $url = Router::url('auth.verify-email', ['token' => str_repeat('a', 64)]);

        $this->assertSame(
            'http://localhost/SeneBridge/public/verify-email/' . str_repeat('a', 64),
            $url
        );
    }

    public function testResolveUnknownRouteReturns404(): void
    {
        $_SERVER['REQUEST_URI'] = '/SeneBridge/public/route-inconnue-xyz';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $response = Router::resolve(new Request());

        $this->assertSame(404, $response->status);
        $this->assertStringContainsString('404', $response->content);
    }

    public function testResolveHomeRouteReturns200(): void
    {
        $_SERVER['REQUEST_URI'] = '/SeneBridge/public/';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $response = Router::resolve(new Request());

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('SeneBridge', $response->content);
    }
}