<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testJsonEnvelopesBareArrays(): void
    {
        $response = Response::json(['foo' => 'bar']);

        $decoded = json_decode($response->content, true);

        $this->assertSame(200, $response->status);
        $this->assertTrue($decoded['success']);
        $this->assertSame(['foo' => 'bar'], $decoded['data']);
        $this->assertNull($decoded['message']);
        $this->assertSame([], $decoded['errors']);
    }

    public function testJsonDoesNotDoubleWrapEnvelope(): void
    {
        $response = Response::json([
            'success' => true,
            'data' => ['id' => 1],
            'message' => 'OK',
            'errors' => [],
        ], 201);

        $decoded = json_decode($response->content, true);

        $this->assertSame(201, $response->status);
        $this->assertSame(true, $decoded['success']);
        $this->assertSame(['id' => 1], $decoded['data']);
        $this->assertArrayNotHasKey('success', $decoded['data']);
    }

    public function testErrorProducesFailureEnvelope(): void
    {
        $response = Response::error('Jeton invalide', 401);

        $decoded = json_decode($response->content, true);

        $this->assertSame(401, $response->status);
        $this->assertFalse($decoded['success']);
        $this->assertSame('Jeton invalide', $decoded['message']);
        $this->assertNull($decoded['data']);
    }

    public function testErrorCarriesFieldErrors(): void
    {
        $response = Response::error('Données invalides', 422, ['email' => 'Adresse requise']);

        $decoded = json_decode($response->content, true);

        $this->assertSame(['email' => 'Adresse requise'], $decoded['errors']);
    }
}