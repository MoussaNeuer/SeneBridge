<?php
/**
 * Page 404. Variables : $message (optionnel).
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page introuvable (404) — SeneBridge</title>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-screen bg-cream text-ink grid place-items-center px-4 py-16">
    <div class="text-center max-w-lg">
        <p class="text-7xl font-extrabold text-brand">404</p>
        <h1 class="text-2xl font-extrabold mt-4">Page introuvable</h1>
        <p class="mt-2 opacity-75 leading-relaxed">
            <?= e($message ?? 'La page demandée n\'existe pas ou a été déplacée.') ?>
        </p>
        <div class="mt-8 flex justify-center gap-4">
            <a href="<?= e(app_url('/')) ?>" class="px-6 py-3 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">
                Retour à l'accueil
            </a>
            <a href="<?= e(app_url('login')) ?>" class="px-6 py-3 rounded-lg border border-brand text-brand font-semibold hover:bg-brand/5 transition">
                Espace client
            </a>
        </div>
    </div>
</body>
</html>