<?php
use App\Support\App;
use App\Support\CSRF;
/**
 * Layout principal du site public SeneBridge.
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->yield('title') ?: 'SeneBridge — Votre lien avec le Sénégal' ?></title>
    <meta name="description" content="<?= e($metaDescription ?? 'SeneBridge, votre plateforme de gestion clients, biens et projets au Sénégal.') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-screen flex flex-col bg-cream text-ink">

    <header class="bg-brand text-brand-foreground">
        <div class="max-w-7xl mx-auto px-4 py-2 text-sm flex items-center justify-between border-b border-white/10">
            <span>📍 Dakar, Sénégal · Diaspora</span>
            <span>✉ contact@senebridge.sn · ☎ +221 33 000 00 00</span>
        </div>
        <nav class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between gap-6">
            <a href="<?= e(app_url('/')) ?>" class="flex items-center gap-2 text-xl font-bold tracking-tight">
                <span class="inline-block w-9 h-9 rounded-lg bg-gold text-brand grid place-items-center font-extrabold">S</span>
                SeneBridge
            </a>
            <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="<?= e(app_url('/')) ?>#services" class="hover:text-gold">Nos services</a>
                <a href="<?= e(app_url('/')) ?>#apropos" class="hover:text-gold">À propos</a>
                <a href="<?= e(app_url('/')) ?>#contact" class="hover:text-gold">Contact</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= e(app_url('login')) ?>" class="px-4 py-2 rounded-lg border border-white/30 hover:bg-white/10 transition">Connexion</a>
                <a href="<?= e(app_url('register')) ?>" class="px-4 py-2 rounded-lg bg-gold text-brand font-semibold hover:bg-gold-light transition">Démarrer</a>
            </div>
        </nav>
    </header>

    <?php if (($flashes = \App\Support\App::flashes())): ?>
    <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
        <?php foreach ($flashes as $type => $message): ?>
            <div class="px-4 py-3 rounded-lg text-sm font-medium border
                <?= $type === 'success' ? 'bg-emerald-50 border-emerald-brand text-emerald-brand' : 'bg-red-50 border-red-400 text-red-700' ?>"
                 role="alert">
                <?= e($message) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <main class="flex-1">
        <?= $this->yield('content') ?>
    </main>

    <footer class="bg-ink text-cream mt-16">
        <div class="max-w-7xl mx-auto px-4 py-10 grid gap-8 md:grid-cols-3 text-sm">
            <div>
                <p class="font-bold text-lg mb-2">SeneBridge</p>
                <p class="opacity-80">Votre lien avec le Sénégal. Immobilier, gestion de projets, import/export, conciergerie et investissement.</p>
            </div>
            <div>
                <p class="font-bold mb-2">Navigation</p>
                <ul class="space-y-1 opacity-80">
                    <li><a href="<?= e(app_url('/')) ?>" class="hover:text-gold">Accueil</a></li>
                    <li><a href="<?= e(app_url('login')) ?>" class="hover:text-gold">Espace client</a></li>
                    <li><a href="<?= e(app_url('register')) ?>" class="hover:text-gold">Créer un compte</a></li>
                </ul>
            </div>
            <div>
                <p class="font-bold mb-2">Contact</p>
                <p class="opacity-80">Dakar, Sénégal</p>
                <p class="opacity-80">contact@senebridge.sn</p>
            </div>
        </div>
        <div class="border-t border-white/10 py-4 text-center text-xs opacity-70">
            © <?= date('Y') ?> SeneBridge — Mentions légales · Politique de confidentialité
        </div>
    </footer>

</body>
</html>
<?php
unset($flashes);