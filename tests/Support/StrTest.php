<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Str;
use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    public function testTokenIsHexOfExpectedLength(): void
    {
        $token = Str::token(32);

        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testTokenRandomness(): void
    {
        $this->assertNotSame(Str::token(32), Str::token(32));
    }

    public function testRandomLength(): void
    {
        $this->assertSame(32, strlen(Str::random(32)));
    }

    public function testProjectReferenceFormat(): void
    {
        $ref = Str::projectReference('SEN', 7);

        $this->assertSame('SEN-' . date('Y') . '-0007', $ref);
    }

    public function testSlug(): void
    {
        $this->assertSame('gestion-de-projets', Str::slug('Gestion de Projets'));
        $this->assertSame('sen', Str::slug('  SEN  '));
    }

    public function testIsEmail(): void
    {
        $this->assertTrue(Str::isEmail('client@example.com'));
        $this->assertFalse(Str::isEmail('pas-un-email'));
    }
}