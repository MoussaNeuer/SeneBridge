<?php
/**
 * Email : confirmation d'adresse e-mail.
 * Données : [$token, $appName, $expiryMinutes]
 */
$url = app_url('verify-email/' . rawurlencode($token));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmez votre adresse e-mail — <?= e($appName) ?></title>
</head>
<body style="margin:0;padding:0;background:#F8F7F2;font-family:Arial,Helvetica,sans-serif;color:#17221F;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F8F7F2;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#FFFFFF;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="background:#005B4F;padding:28px 32px;color:#FFFFFF;">
                        <span style="font-size:22px;font-weight:bold;">SeneBridge</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <h1 style="margin:0 0 12px;font-size:20px;">Confirmez votre adresse e-mail</h1>
                        <p style="font-size:14px;line-height:1.6;">
                            Merci de créer votre compte. Pour activer votre accès à l'espace client,
                            confirmez votre adresse en cliquant sur le bouton ci-dessous.
                        </p>
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                            <tr>
                                <td style="border-radius:8px;background:#D8A84E;">
                                    <a href="<?= e($url) ?>"
                                       style="display:inline-block;padding:12px 24px;color:#17221F;text-decoration:none;font-weight:bold;font-size:14px;border-radius:8px;">
                                        Confirmer mon adresse e-mail
                                    </a>
                                </td>
                            </tr>
                        </table>
                        <p style="font-size:13px;color:#5B6B66;line-height:1.6;">
                            Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :
                            <br>
                            <a href="<?= e($url) ?>" style="color:#005B4F;"><?= e($url) ?></a>
                        </p>
                        <p style="font-size:12px;color:#8A9791;margin-top:24px;">
                            Ce lien expire dans <?= e($expiryMinutes) ?> minutes.
                            Si vous n'avez pas créé de compte, ignorez cet e-mail.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="background:#17221F;color:#F8F7F2;padding:16px 32px;font-size:12px;text-align:center;">
                        © <?= date('Y') ?> <?= e($appName) ?> — Votre lien avec le Sénégal
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>