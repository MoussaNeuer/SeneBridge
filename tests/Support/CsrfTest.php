<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\CSRF;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    public function testTokenIsStableWithinSession(): void
    {
        $a = CSRF::token();
        $b = CSRF::token();

        $this->assertSame(64, strlen($a));
        $this->assertSame($a, $b);
        $this->assertTrue(CSRF::verify($a));
        $this->assertFalse(CSRF::verify('x'));
        $this->assertFalse(CSRF::verify(null));
    }
}