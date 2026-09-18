<?php
/**
 * Page d'erreur générique (500). Variables : $message, $exception (mode debug).
 */
$isDebug = isset($exception);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Une erreur est survenue — SeneBridge</title>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-screen bg-cream text-ink grid place-items-center px-4 py-16">
    <div class="text-center max-w-xl w-full">
        <p class="text-7xl font-extrabold text-brand">500</p>
        <h1 class="text-2xl font-extrabold mt-4">Une erreur est survenue</h1>
        <p class="mt-2 opacity-75 leading-relaxed">
            Une erreur inattendue a interrompu votre requête. Notre équipe a été notifiée ; réessayez dans quelques instants.
        </p>

        <?php if ($isDebug): ?>
        <div class="mt-6 text-left bg-white border border-red-300 rounded-xl p-6 overflow-auto text-xs">
            <p class="font-bold text-red-700"><?= e($message ?? 'Erreur inconnue') ?></p>
            <p class="mt-1 opacity-70"><?= e(get_class($exception)) ?></p>
            <pre class="mt-3 whitespace-pre-wrap opacity-80"><?= e($exception->getTraceAsString()) ?></pre>
        </div>
        <?php endif; ?>

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