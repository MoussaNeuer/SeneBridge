<?php

declare(strict_types=1);

namespace App\Support;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Expéditeur de courriels.
 *
 * Drivers : 'log' (écriture dans storage/logs/mail.log, pratique en dev) et
 * 'smtp' (PHPMailer, pour la production). Le driver SMTP force un échec
 * propre (retour false + journal) si les identifiants sont absents.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, array $options = []): bool
    {
        $driver = config('mail.default', 'log');

        return match ($driver) {
            'log' => self::sendViaLog($to, $subject, $htmlBody),
            'smtp' => self::sendViaSmtp($to, $subject, $htmlBody),
            default => throw new \LogicException(sprintf('Driver mail inconnu : %s', $driver)),
        };
    }

    /**
     * Génère le corps HTML d'un template email, puis l'envoie.
     */
    public static function sendTemplate(string $to, string $subject, string $template, array $data = []): bool
    {
        $body = View::render('emails/' . $template, $data);

        return self::send($to, $subject, $body);
    }

    private static function sendViaLog(string $to, string $subject, string $htmlBody): bool
    {
        $from = config('mail.from.address', 'no-reply@senebridge.sn');
        $fromName = config('mail.from.name', 'SeneBridge');
        $path = (string) config('mail.mailers.log.path', storage_path('logs/mail.log'));

        $lines = [
            '=== ' . date('Y-m-d H:i:s') . ' ===',
            'From: ' . $fromName . ' <' . $from . '>',
            'To: ' . $to,
            'Subject: ' . $subject,
            '',
            self::htmlToText($htmlBody),
            '----------------------------------------',
            '',
        ];

        return (bool) file_put_contents($path, implode("\n", $lines), FILE_APPEND | LOCK_EX);
    }

    /**
     * Envoi SMTP réel via PHPMailer (production).
     */
    private static function sendViaSmtp(string $to, string $subject, string $htmlBody): bool
    {
        $from = (string) config('mail.from.address', 'no-reply@senebridge.sn');
        $fromName = (string) config('mail.from.name', 'SeneBridge');
        $username = (string) config('mail.mailers.smtp.username', '');

        if ($from === '' || $username === '') {
            Log::warning('SMTP non configuré, courriel non envoyé', ['to' => $to, 'subject' => $subject]);

            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = (string) config('mail.mailers.smtp.host', '');
            $mail->SMTPAuth = (bool) config('mail.mailers.smtp.auth', true);
            $mail->Username = $username;
            $mail->Password = (string) config('mail.mailers.smtp.password', '');
            $mail->Port = (int) config('mail.mailers.smtp.port', 587);
            $mail->Timeout = (int) config('mail.mailers.smtp.timeout', 15);

            $encryption = (string) config('mail.mailers.smtp.encryption', 'tls');
            $mail->SMTPSecure = match ($encryption) {
                'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                'tls' => PHPMailer::ENCRYPTION_STARTTLS,
                default => '',
            };

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Encoding = 'base64';
            $mail->XMailer = 'SeneBridge';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            $mail->AltBody = self::htmlToText($htmlBody);

            return $mail->send();
        } catch (\Throwable $e) {
            Log::error('Échec de l\'envoi SMTP', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Convertit un corps HTML en texte brut (version AltBody et log).
     */
    private static function htmlToText(string $htmlBody): string
    {
        $text = str_replace(
            ["\r\n", "\r", '<br>', '<br/>', '</p>', '</div>', '</li>'],
            ["\n", "\n", "\n", "\n", "\n\n", "\n", "\n- "],
            $htmlBody
        );

        $text = (string) preg_replace('/\n{3,}/', "\n\n", strip_tags($text));
        $text = trim((string) preg_replace('/[ \t]+/', ' ', $text));

        return $text;
    }
}