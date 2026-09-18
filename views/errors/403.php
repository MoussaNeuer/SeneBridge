<?php
/**
 * Page 403. Variables : $message (optionnel).
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé (403) — SeneBridge</title>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-screen bg-cream text-ink grid place-items-center px-4 py-16">
    <div class="text-center max-w-lg">
        <p class="text-7xl font-extrabold text-brand">403</p>
        <h1 class="text-2xl font-extrabold mt-4">Accès refusé</h1>
        <p class="mt-2 opacity-75 leading-relaxed">
            <?= e($message ?? 'Vous n\'avez pas la permission d\'accéder à cette ressource.') ?>
        </p>
        <div class="mt-8 flex justify-center gap-4">
            <a href="<?= e(app_url('/')) ?>" class="px-6 py-3 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">
                Retour à l'accueil
            </a>
        </div>
    </div>
</body>
</html>