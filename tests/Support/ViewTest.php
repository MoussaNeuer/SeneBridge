<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\View;
use PHPUnit\Framework\TestCase;

final class ViewTest extends TestCase
{
    public function testRenderHomeContainsBrandAndServices(): void
    {
        $html = View::render('public/home', [
            'services' => [
                ['title' => 'Immobilier', 'description' => 'Achat, vente et accompagnement.'],
            ],
        ]);

        $this->assertStringContainsString('SeneBridge', $html);
        $this->assertStringContainsString('Immobilier', $html);
        $this->assertStringContainsString('Le pont entre la', $html);
    }

    public function testLoginViewContainsCsrfField(): void
    {
        $html = View::render('auth/login');

        $this->assertStringContainsString('name="_token"', $html);
    }

    public function testOutputIsHtmlEscaped(): void
    {
        $html = View::render('errors/404', ['message' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testMissingTemplateThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        View::render('nope/does-not-exist');
    }
}