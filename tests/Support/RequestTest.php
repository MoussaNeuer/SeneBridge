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
}