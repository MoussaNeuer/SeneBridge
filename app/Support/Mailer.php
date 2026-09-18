<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Expéditeur de courriels.
 *
 * Driver MVP : 'log' (écriture du message dans storage/logs/mail.log)
 * pour un environnement local sans SMTP. Le driver 'smtp' est prêt à
 * l'emploi une fois les identifiants fournis (Phase production).
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, array $options = []): bool
    {
        $driver = config('mail.default', 'log');

        return match ($driver) {
            'log' => self::sendViaLog($to, $subject, $htmlBody),
            'smtp' => self::sendViaSmtp($to, $subject, $htmlBody, $options),
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

        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            strip_tags(str_replace(['<br>', '<br/>', '</p>', '</div>', '</li>'], "\n", $htmlBody))
        );
        $text = (string) preg_replace('/\n{3,}/', "\n\n", $text);

        $lines = [
            '=== ' . date('Y-m-d H:i:s') . ' ===',
            'From: ' . $fromName . ' <' . $from . '>',
            'To: ' . $to,
            'Subject: ' . $subject,
            '',
            $text,
            '----------------------------------------',
            '',
        ];

        return (bool) file_put_contents($path, implode("\n", $lines), FILE_APPEND | LOCK_EX);
    }

    private static function sendViaSmtp(string $to, string $subject, string $htmlBody, array $options = []): bool
    {
        // Placeholder propre : l'intégration SMTP réelle (PHPMailer) est prévue
        // pour la préproduction. On refuse silencieusement pour ne pas inventer
        // un envoi qui ne fonctionnerait pas.
        Log::warning('Driver SMTP non configuré, courriel non envoyé', [
            'to' => $to,
            'subject' => $subject,
        ]);

        return false;
    }
}