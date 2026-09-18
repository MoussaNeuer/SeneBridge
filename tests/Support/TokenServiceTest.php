<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\TokenService;
use App\Support\Database;
use App\Support\Hasher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class TokenServiceTest extends TestCase
{
    private static ?int $userId = null;

    public static function setUpBeforeClass(): void
    {
        $email = 'token-user-' . bin2hex(random_bytes(4)) . '@tests.senebridge.local';

        static::$userId = (int) Database::insert(
            'INSERT INTO users (public_id, role_id, first_name, last_name, email, password_hash, status) '
            . 'VALUES (?, (SELECT id FROM roles WHERE name = ?), ?, ?, ?, ?, ?)',
            [
                (new Ulid())->toBase32(),
                'client',
                'Token',
                'User',
                $email,
                Hasher::make('TestPassword2026!'),
                'active',
            ]
        );
    }

    public static function tearDownAfterClass(): void
    {
        Database::statement('SET FOREIGN_KEY_CHECKS = 0');
        Database::statement('DELETE FROM email_verification_tokens WHERE user_id = ?', [static::$userId]);
        Database::statement('DELETE FROM password_reset_tokens WHERE user_id = ?', [static::$userId]);
        Database::statement('DELETE FROM audit_logs WHERE user_id = ?', [static::$userId]);
        Database::statement('DELETE FROM users WHERE id = ?', [static::$userId]);
        Database::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testIssueAndConsumeRoundTrip(): void
    {
        $table = 'email_verification_tokens';
        $raw = TokenService::issue($table, (int) static::$userId, 30);

        $this->assertSame(64, strlen($raw));

        $consumed = TokenService::consume($table, $raw);

        $this->assertSame((int) static::$userId, $consumed);

        // Un jeton consommé ne doit plus être réutilisable.
        $this->assertNull(TokenService::consume($table, $raw));
    }

    public function testConsumeRejectsWrongEmail(): void
    {
        $table = 'password_reset_tokens';
        $raw = TokenService::issue($table, (int) static::$userId, 30);

        $this->assertNull(TokenService::consume($table, $raw, 'autre@example.com'));

        // Un mauvais e-mail ne consomme pas le jeton : le bon e-mail fonctionne toujours.
        $email = Database::first('SELECT email FROM users WHERE id = ?', [static::$userId])['email'] ?? '';

        $this->assertSame((int) static::$userId, TokenService::consume($table, $raw, $email));
    }
}