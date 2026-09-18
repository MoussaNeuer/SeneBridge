<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Validators\AuthValidator;
use PHPUnit\Framework\TestCase;

final class AuthValidatorTest extends TestCase
{
    public function testRegisterValidDataPasses(): void
    {
        $validation = AuthValidator::register([
            'first_name' => 'Aminata',
            'last_name' => 'Sow',
            'email' => 'aminata@example.com',
            'phone' => '+221771234567',
            'password' => 'AminataSecure2026!',
            'password_confirmation' => 'AminataSecure2026!',
        ]);

        $this->assertTrue($validation->passes());
    }

    public function testRegisterInvalidEmailFails(): void
    {
        $validation = AuthValidator::register([
            'first_name' => 'Aminata',
            'last_name' => 'Sow',
            'email' => 'adresse-invalide',
            'phone' => '',
            'password' => 'AminataSecure2026!',
            'password_confirmation' => 'AminataSecure2026!',
        ]);

        $this->assertFalse($validation->passes());
        $this->assertNotEmpty($validation->errors());
    }

    public function testRegisterShortPasswordFails(): void
    {
        $validation = AuthValidator::register([
            'first_name' => 'Aminata',
            'last_name' => 'Sow',
            'email' => 'aminata2@example.com',
            'phone' => '',
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
        ]);

        $this->assertFalse($validation->passes());
    }

    public function testLoginEmptyPasswordFails(): void
    {
        $validation = AuthValidator::login(['email' => 'x@example.com', 'password' => '']);

        $this->assertFalse($validation->passes());
    }
}