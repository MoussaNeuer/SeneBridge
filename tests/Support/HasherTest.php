<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Hasher;
use PHPUnit\Framework\TestCase;

final class HasherTest extends TestCase
{
    public function testMakeAndVerify(): void
    {
        $hash = Hasher::make('MotDePasseTresSecurise2026!');

        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertTrue(Hasher::verify('MotDePasseTresSecurise2026!', $hash));
        $this->assertFalse(Hasher::verify('mauvais', $hash));
    }

    public function testNeedsRehashIsFalseForFreshHash(): void
    {
        $hash = Hasher::make('MotDePasseTresSecurise2026!');

        $this->assertFalse(Hasher::needsRehash($hash));
    }

    public function testVerifyRejectsEmptyExpected(): void
    {
        $this->assertFalse(Hasher::verify('x', ''));
    }
}