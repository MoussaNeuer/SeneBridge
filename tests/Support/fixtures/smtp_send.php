<?php

declare(strict_types=1);

/**
 * Fixture exécutée dans un sous-processus : envoie un courriel via le driver SMTP
 * vers un serveur local factice. Sorties : "SENT:1" ou "SENT:0".
 *
 * Les variables d'environnement MAIL_* sont positionnées ici (et non dans un
 * phpunit.xml) pour que le sous-processus soit le seul concerné.
 */

$port = $argv[1] ?? '';
if ($port === '' || !ctype_digit($port)) {
    fwrite(STDERR, "Port SMTP manquant.\n");
    exit(1);
}

putenv('MAIL_MAILER=smtp');
putenv('MAIL_HOST=127.0.0.1');
putenv('MAIL_PORT=' . $port);
putenv('MAIL_USERNAME=user@fake.test');
putenv('MAIL_PASSWORD=secret');
putenv('MAIL_ENCRYPTION=none');
putenv('MAIL_AUTH=false');
putenv('MAIL_FROM_ADDRESS=no-reply@senebridge.sn');
putenv('MAIL_FROM_NAME=SeneBridge Test');

$_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['REQUEST_METHOD'] ??= 'GET';
$_SERVER['argv'] ??= ['fixture'];

require dirname(__DIR__, 3) . '/vendor/autoload.php';
require dirname(__DIR__, 3) . '/tests/bootstrap.php';

$ok = \App\Support\Mailer::send(
    'dest@fake.test',
    'Test SMTP',
    '<p>Bonjour depuis le test.</p>'
);

echo $ok ? 'SENT:1' : 'SENT:0';
exit($ok ? 0 : 1);