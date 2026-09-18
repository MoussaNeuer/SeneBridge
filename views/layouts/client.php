<?php
use App\Support\App;
use App\Support\CSRF;
use App\Support\Gate;
use App\Support\Router;
/**
 * Layout de l'espace client (et du personnel).
 */
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$_nav = [
    ['label' => 'Tableau de bord', 'route' => 'dashboard'],
    ['label' => 'Mes projets', 'route' => 'client.projects'],
    ['label' => 'Notifications', 'route' => 'client.notifications'],
    ['label' => 'Mon profil', 'route' => 'client.profile'],
];
$_isActive = static function (string $route) use ($currentPath): bool {
    $url = Router::url($route);
    $path = parse_url($url, PHP_URL_PATH) ?? $url;

    return $currentPath === $path || str_starts_with($currentPath, rtrim($path, '/') . '/');
};
if (!isset($user)) {
    $user = App::user();
}
if (!isset($unreadCount)) {
    $unreadCount = $user !== null ? \App\Models\Notification::countUnread((int) $user['id']) : 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->yield('title') ?: 'Mon espace — SeneBridge' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-screen bg-cream text-ink flex flex-col">

<div class="flex flex-col lg:flex-row min-h-screen">
    <aside class="bg-brand text-brand-foreground w-full lg:w-64 shrink-0 lg:min-h-screen">
        <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
            <a href="<?= e(app_url('/')) ?>" class="flex items-center gap-2 font-bold text-lg">
                <span class="inline-block w-8 h-8 rounded-lg bg-gold text-brand grid place-items-center font-extrabold text-sm">S</span>
                SeneBridge
            </a>
            <span class="lg:hidden text-xs">☰</span>
        </div>

        <nav class="px-3 py-4 space-y-1 text-sm">
            <?php foreach ($_nav as $item): ?>
                <?php $url = Router::url($item['route']); ?>
                <a href="<?= e($url) ?>" class="block px-3 py-2 rounded-lg transition <?= $_isActive($item['route']) ? 'bg-white/15 text-white' : 'text-white/75 hover:bg-white/10' ?>">
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="px-5 py-4 border-t border-white/10 text-xs text-white/60">
            Connecté en tant que
            <span class="block font-semibold text-white/90">
                <?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>
            </span>
        </div>
    </aside>

    <div class="flex-1 flex flex-col">
        <header class="bg-white border-b border-brand/10 px-6 py-3 flex items-center justify-between gap-4">
            <p class="text-sm text-ink/70 font-medium"><?= $this->yield('title') ?></p>
            <div class="flex items-center gap-3">
                <a href="<?= e(Router::url('client.notifications')) ?>"
                   class="relative text-sm px-3 py-1.5 rounded-lg border border-brand/10 hover:bg-cream transition">
                    🔔
                    <?php if (($unreadCount ?? 0) > 0): ?>
                        <span class="absolute -top-1.5 -right-1.5 min-w-5 h-5 px-1 rounded-full bg-gold text-brand text-[10px] font-bold grid place-items-center">
                            <?= (int) $unreadCount ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="<?= e(app_url('/')) ?>" class="text-sm px-3 py-1.5 rounded-lg border border-brand/10 hover:bg-cream transition">Site public</a>
                <form method="POST" action="<?= e(app_url('logout')) ?>" class="inline">
                    <?= CSRF::field() ?>
                    <button type="submit" class="text-sm px-3 py-1.5 rounded-lg bg-brand text-brand-foreground hover:bg-brand-dark transition">Déconnexion</button>
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
            © <?= date('Y') ?> SeneBridge — Espace client · <?= e(Gate::roleName($user ?? null) ?: 'membre') ?>
        </footer>
    </div>
</div>

</body>
</html>
<?php unset($flashes); ?>