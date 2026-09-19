<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase;

/**
 * Vérifie le driver de mail :
 *  - 'log' écrit un message dans le fichier de journal ;
 *  - 'smtp' parle réellement à un serveur SMTP factice (sous-processus).
 */
final class MailerTest extends TestCase
{
    public function testLogDriverWritesEntry(): void
    {
        $path = (string) config('mail.mailers.log.path', storage_path('logs/mail.log'));

        \App\Support\Mailer::send('dest@fake.test', 'Sujet test', '<p>Hello</p>');
        $written = (string) @file_get_contents($path);

        $this->assertStringContainsString('Sujet test', $written);
        $this->assertStringContainsString('Hello', $written);
    }

    public function testSmtpDriverSendsThroughFakeServer(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertNotFalse($server, sprintf('Serveur SMTP factice : %s', $errstr));
        $address = stream_socket_get_name($server, false);
        $port = (int) substr($address, strrpos($address, ':') + 1);

        $fixture = dirname(__DIR__) . '/Support/fixtures/smtp_send.php';
        $process = proc_open(
            [PHP_BINARY, $fixture, (string) $port],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );
        $this->assertNotFalse($process);

        if (is_resource($pipes[0])) {
            fclose($pipes[0]);
        }

        $received = '';
        $conn = stream_socket_accept($server, 15);
        $this->assertNotFalse($conn, 'Aucune connexion SMTP reçue par le serveur factice.');
        if (is_resource($conn)) {
            stream_set_timeout($conn, 15);
            fwrite($conn, "220 fake.test ESMTP\r\n");

            $inData = false;
            while (!feof($conn)) {
                $line = fgets($conn);
                if ($line === false) {
                    break;
                }
                $line = trim($line);
                $received .= $line . "\n";

                if ($inData) {
                    if ($line === '.') {
                        fwrite($conn, "250 OK: queued\r\n");
                        $inData = false;
                    }
                    continue;
                }

                $verb = strtoupper((string) substr($line, 0, 4));
                switch ($verb) {
                    case 'EHLO':
                    case 'HELO':
                        fwrite($conn, "250-fake.test\r\n250 OK\r\n");
                        break;
                    case 'MAIL':
                    case 'RCPT':
                        fwrite($conn, "250 OK\r\n");
                        break;
                    case 'DATA':
                        fwrite($conn, "354 End data with <CR><LF>.<CR><LF>\r\n");
                        $inData = true;
                        break;
                    case 'QUIT':
                        fwrite($conn, "221 Bye\r\n");
                        break 2;
                    default:
                        fwrite($conn, "250 OK\r\n");
                }
            }
            fclose($conn);
        }
        fclose($server);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        $this->assertSame(0, $code, 'Le sous-processus SMTP a échoué : ' . $stderr);
        $this->assertStringContainsString('SENT:1', $stdout);
        $this->assertStringContainsString('MAIL FROM:', $received);
        $this->assertStringContainsString('RCPT TO:<dest@fake.test>', $received);
        $this->assertStringContainsString('DATA', $received);
        $this->assertStringContainsString('Subject: Test SMTP', $received);
    }
}