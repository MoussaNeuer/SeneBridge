<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testPathStripsConfiguredBase(): void
    {
        $_SERVER['REQUEST_URI'] = '/SeneBridge/public/login';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $request = new Request();

        $this->assertSame('/login', $request->path());
    }

    public function testPathWithoutBaseKeepsRoot(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $request = new Request();

        $this->assertSame('/', $request->path());
    }

    public function testPathNormalizesEmptyToRoot(): void
    {
        $_SERVER['REQUEST_URI'] = '/SeneBridge/public';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $request = new Request();

        $this->assertSame('/', $request->path());
    }

    public function testBearerTokenParsedFromAuthorizationHeader(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer sbt_0123456789abcdef';

        $request = new Request();

        $this->assertSame('sbt_0123456789abcdef', $request->bearerToken());
    }

    public function testBearerTokenNullWithoutHeader(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $request = new Request();

        $this->assertNull($request->bearerToken());
    }

    public function testBearerTokenRejectsMalformedHeader(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic Zm9vOmJhcg==';

        $request = new Request();

        $this->assertNull($request->bearerToken());
    }
}