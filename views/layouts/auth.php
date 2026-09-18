<?php
use App\Support\CSRF;
/**
 * Layout des écrans d'authentification.
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->yield('title') ?> — SeneBridge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-screen bg-brand grid place-items-center px-4 py-10 text-ink">

    <div class="w-full max-w-md">
        <div class="flex justify-center mb-6">
            <a href="<?= e(app_url('/')) ?>" class="flex items-center gap-2 text-2xl font-bold text-brand-foreground">
                <span class="inline-block w-10 h-10 rounded-xl bg-gold text-brand grid place-items-center font-extrabold">S</span>
                SeneBridge
            </a>
        </div>

        <?php if (($flashes = \App\Support\App::flashes())): ?>
            <div class="mb-4 space-y-2">
                <?php foreach ($flashes as $type => $message): ?>
                    <div class="px-4 py-3 rounded-lg text-sm font-medium border
                        <?= $type === 'success' ? 'bg-emerald-50 border-emerald-brand text-emerald-brand' : 'bg-red-50 border-red-400 text-red-700' ?>"
                         role="alert">
                        <?= e($message) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="bg-cream rounded-2xl shadow-xl p-8">
            <?= $this->yield('content') ?>
        </div>

        <p class="text-center text-sm text-brand-foreground/70 mt-6">
            © <?= date('Y') ?> SeneBridge — Votre lien avec le Sénégal
        </p>
    </div>

</body>
</html>
<?php
unset($flashes);