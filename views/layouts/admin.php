<?php
use App\Support\App;
use App\Support\CSRF;
use App\Support\Gate;
use App\Support\Router;
/**
 * Layout du back-office SeneBridge.
 */
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$_isActive = static function (string $route) use ($currentPath): bool {
    $url = Router::url($route);
    $path = parse_url($url, PHP_URL_PATH) ?? $url;

    return $currentPath === $path || str_starts_with($currentPath, rtrim($path, '/') . '/');
};
$_can = static fn (string $p): bool => Gate::allows($p);
$nav = [
    ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'permission' => 'admin.access'],
    ['label' => 'Projets', 'route' => 'admin.projects', 'permission' => 'projects.view'],
    ['label' => 'Demandes reçues', 'route' => 'admin.requests', 'permission' => 'requests.view'],
    ['label' => 'Clients', 'route' => 'admin.clients', 'permission' => 'users.view'],
    ['label' => 'Conseillers', 'route' => 'admin.counselors', 'permission' => 'users.assign_counselor'],
    ['label' => 'Rôles & droits', 'route' => 'admin.roles', 'permission' => 'roles.view'],
    ['label' => 'Biens immobiliers', 'route' => 'admin.properties', 'permission' => 'properties.view'],
    ['label' => 'Factures', 'route' => 'admin.invoices', 'permission' => 'invoices.view'],
    ['label' => 'Paiements', 'route' => 'admin.payments', 'permission' => 'payments.view'],
    ['label' => 'Messagerie', 'route' => 'admin.messages', 'permission' => 'messages.view'],
    ['label' => 'Rendez-vous', 'route' => 'admin.appointments', 'permission' => 'appointments.view'],
    ['label' => 'Messages contact', 'route' => 'admin.contacts', 'permission' => 'contacts.view'],
    ['label' => 'Actualités', 'route' => 'admin.articles', 'permission' => 'articles.manage'],
];
if (!isset($user)) {
    $user = App::user();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->yield('title') ?: 'Back-office — SeneBridge' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-screen bg-cream text-ink flex flex-col">

<div class="flex flex-col lg:flex-row min-h-screen">
    <aside class="bg-ink text-cream w-full lg:w-60 shrink-0 lg:min-h-screen">
        <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
            <a href="<?= e(Router::url('admin.dashboard')) ?>" class="flex items-center gap-2 font-bold text-lg">
                <span class="inline-block w-8 h-8 rounded-lg bg-gold text-brand grid place-items-center font-extrabold text-sm">S</span>
                Back-office
            </a>
        </div>
        <nav class="px-3 py-4 space-y-1 text-sm">
            <?php foreach ($nav as $item): ?>
                <?php if (!$_can($item['permission'])): continue; endif; ?>
                <a href="<?= e(Router::url($item['route'])) ?>"
                   class="block px-3 py-2 rounded-lg transition <?= $_isActive($item['route']) ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10' ?>">
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
            <div class="pt-2 mt-2 border-t border-white/10">
                <a href="<?= e(app_url('/')) ?>" class="block px-3 py-2 rounded-lg text-white/70 hover:bg-white/10 transition">Retour au site →</a>
            </div>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col">
        <header class="bg-white border-b border-brand/10 px-6 py-3 flex items-center justify-between gap-4">
            <p class="text-sm text-ink/70 font-medium"><?= $this->yield('title') ?></p>
            <div class="flex items-center gap-3 text-sm">
                <span class="hidden md:inline text-ink/60">
                    <?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>
                    · <span class="font-semibold text-brand"><?= e(Gate::roleName($user) ?: '') ?></span>
                </span>
                <a href="<?= e(app_url('dashboard')) ?>" class="px-3 py-1.5 rounded-lg border border-brand/10 hover:bg-cream transition">Mon espace</a>
                <form method="POST" action="<?= e(app_url('logout')) ?>" class="inline">
                    <?= CSRF::field() ?>
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-brand text-brand-foreground hover:bg-brand-dark transition">Déconnexion</button>
                </form>
            </div>
        </header>

        <?php if (($flashes = App::flashes())): ?>
        <div class="px-6 pt-4 w-full">
            <?php foreach ($flashes as $type => $message): ?>
                <div class="px-4 py-3 rounded-lg text-sm font-medium border
                    <?= $type === 'success' ? 'bg-emerald-50 border-emerald-brand text-emerald-brand' : 'bg-red-50 border-red-400 text-red-700' ?>"
                     role="alert">
                    <?= e($message) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <main class="flex-1 p-6">
            <?= $this->yield('content') ?>
        </main>

        <footer class="px-6 py-4 text-xs text-ink/50 border-t border-brand/10">
            © <?= date('Y') ?> SeneBridge — Back-office
        </footer>
    </div>
</div>

</body>
</html>
<?php unset($flashes); ?>