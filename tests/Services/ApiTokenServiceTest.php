<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\ApiTokenService;
use PHPUnit\Framework\TestCase;

final class ApiTokenServiceTest extends TestCase
{
    public function testGenerateTokenPairReturnsUniqueTokens(): void
    {
        $a = ApiTokenService::generateTokenPair();
        $b = ApiTokenService::generateTokenPair();

        $this->assertNotSame($a['token'], $b['token']);
        $this->assertNotSame($a['hash'], $b['hash']);
    }

    public function testGenerateTokenPairHashMatchesToken(): void
    {
        $pair = ApiTokenService::generateTokenPair();

        $this->assertSame(64, strlen($pair['token']));
        $this->assertSame(64, strlen($pair['hash']));
        $this->assertSame(
            hash(ApiTokenService::HASH_ALGO, $pair['token']),
            $pair['hash']
        );
    }

    public function testGenerateTokenPairIsEntropic(): void
    {
        $seen = [];

        for ($i = 0; $i < 50; $i++) {
            $token = ApiTokenService::generateTokenPair()['token'];
            $this->assertArrayNotHasKey($token, $seen);
            $seen[$token] = true;
        }
    }
}