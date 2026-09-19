<?php
use App\Support\App;
use App\Support\BackofficeWidgets;
use App\Support\CSRF;
use App\Support\Gate;
use App\Support\Router;
/**
 * Layout du back-office SeneBridge — « Back-office Pro ».
 */
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
if (!isset($user)) {
    $user = App::user();
}
$roleName = Gate::roleName($user) ?: '';
$initials = strtoupper(mb_substr(trim((string) ($user['first_name'] ?? '')), 0, 1)
    . mb_substr(trim((string) ($user['last_name'] ?? '')), 0, 1));
$_isActive = static function (string $route) use ($currentPath): bool {
    try {
        $path = parse_url(Router::url($route), PHP_URL_PATH) ?? $route;
    } catch (\Throwable) {
        return false;
    }

    return $currentPath === $path || str_starts_with($currentPath, rtrim($path, '/') . '/');
};
$_link = static function (string $route): string {
    try {
        return Router::url($route);
    } catch (\Throwable) {
        return '#';
    }
};
$_can = static fn (string $p): bool => Gate::allows($p);
$menu = BackofficeWidgets::menu($user);
$_icons = static function (string $name): string {
    return match ($name) {
        'gauge' => '<path d="M3 12a9 9 0 1 1 18 0M3 12h4l2-3 3 5 2-4h3"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/>',
        'wallet' => '<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H3zM16 15h.01"/>',
        'chat' => '<path d="M21 11.5a8.4 8.4 0 0 1-8.5 8.3 8.8 8.8 0 0 1-3.8-.8L3 20l1-3.8a8.3 8.3 0 1 1 17-4.7z"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
        'home' => '<path d="M3 11l9-8 9 8M5 10v10h14V10M9 20v-6h6v6"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4m11.4-11.4 1.4-1.4"/>',
        'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        default => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    };
};
?>
<!DOCTYPE html>
<html lang="fr" class="sb-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(CSRF::token()) ?>" data-counters="<?= e(app_url('admin/api/compteurs')) ?>">
    <title><?= $this->yield('title') ?: 'Back-office — SeneBridge' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-screen bg-cream text-ink flex flex-col">

<div class="sb-progress" aria-hidden="true"><span id="sb-progress-bar"></span></div>

<div class="flex min-h-screen">

    <!-- Overlay mobile -->
    <div id="sb-drawer-overlay" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden" data-close-drawer></div>

    <!-- Sidebar -->
    <aside id="sb-sidebar"
           class="sb-sidebar fixed inset-y-0 left-0 z-40 w-72 -translate-x-full transition-transform duration-200 lg:static lg:translate-x-0 lg:z-auto flex flex-col bg-ink text-cream">
        <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
            <a href="<?= e($_link('admin.dashboard')) ?>" class="flex items-center gap-2.5 font-bold text-lg">
                <span class="inline-grid place-items-center w-9 h-9 rounded-xl bg-gold text-brand font-extrabold">S</span>
                <span>SeneBridge<br><span class="text-xs font-semibold text-white/60 tracking-wide">BACK-OFFICE</span></span>
            </a>
            <button type="button" class="lg:hidden text-white/70 hover:text-white p-1" data-close-drawer aria-label="Fermer le menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div class="px-4 pt-3">
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-white/40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input id="sb-nav-search" type="search" autocomplete="off" placeholder="Rechercher une page…"
                       class="w-full pl-9 pr-3 py-2 rounded-lg bg-white/10 text-sm text-cream placeholder:text-white/40 focus:outline-none focus:ring-2 focus:ring-gold/70">
            </div>
        </div>

        <nav id="sb-nav" class="flex-1 overflow-y-auto px-4 py-4 space-y-5 text-sm">
            <?php foreach ($menu as $section): ?>
                <?php $items = array_values(array_filter($section['items'], static fn (array $i): bool => $_can($i['permission']))); ?>
                <?php if ($items === []): continue; endif; ?>
                <div class="sb-section" data-label="<?= e($section['label']) ?>">
                    <p class="px-3 mb-1.5 text-[11px] font-bold uppercase tracking-wider text-white/40 flex items-center gap-2">
                        <span class="w-4 h-4 grid place-items-center"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $_icons($section['icon']) ?></svg></span>
                        <?= e($section['label']) ?>
                    </p>
                    <div class="space-y-0.5">
                        <?php foreach ($items as $item): ?>
                            <?php $active = $_isActive($item['route']); ?>
                            <a href="<?= e($_link($item['route'])) ?>"
                               class="sb-nav-link group flex items-center justify-between px-3 py-2 rounded-lg transition font-medium
                                      <?= $active ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' ?>"
                               data-search="<?= e(mb_strtolower($section['label'] . ' ' . $item['label'])) ?>">
                                <span class="flex items-center gap-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $active ? 'bg-gold' : 'bg-white/30 group-hover:bg-white/60' ?>"></span>
                                    <?= e($item['label']) ?>
                                </span>
                                <?php $count = isset($item['badge']) ? (int) $item['badge'] : 0; ?>
                                <?php if ($count > 0): ?>
                                    <span class="sb-badge px-1.5 py-0.5 rounded-full text-[10px] font-bold leading-none <?= $active ? 'bg-gold text-brand' : 'bg-white/15 text-white' ?>"
                                          data-counter="<?= e($item['route']) ?>"><?= $count ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="pt-3 border-t border-white/10">
                <a href="<?= e(app_url('/')) ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-white/70 hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $_icons('home') ?></svg>
                    Retour au site
                </a>
            </div>
        </nav>
    </aside>

    <!-- Colonne principale -->
    <div class="flex-1 flex flex-col min-w-0">
        <header class="sb-header sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-brand/10 px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" data-open-drawer class="lg:hidden p-1.5 -ml-1.5 rounded-lg border border-brand/15 hover:bg-cream" aria-label="Ouvrir le menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-extrabold truncate"><?= $this->yield('title') ?></h1>
                    <p class="hidden sm:block text-xs text-ink/50 truncate"><?= e($this->yield('subtitle')) ?>&nbsp;</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" id="sb-theme-toggle" aria-label="Basculer le thème"
                        class="p-2 rounded-lg border border-brand/15 text-ink/70 hover:bg-cream transition">
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $_icons('moon') ?></svg>
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $_icons('sun') ?></svg>
                </button>

                <div class="relative">
                    <button type="button" id="sb-profile-btn" class="flex items-center gap-2.5 p-1.5 pr-3 rounded-xl border border-brand/15 hover:bg-cream transition">
                        <span class="grid place-items-center w-8 h-8 rounded-lg bg-brand text-brand-foreground text-xs font-extrabold"><?= e($initials ?: 'S') ?></span>
                        <span class="hidden md:block text-left leading-tight">
                            <span class="block text-xs font-bold truncate max-w-[140px]"><?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></span>
                            <span class="block text-[11px] text-ink/55"><?= e($roleName) ?></span>
                        </span>
                        <svg class="w-3.5 h-3.5 text-ink/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div id="sb-profile-menu" class="hidden absolute right-0 mt-2 w-56 rounded-xl bg-white dark:bg-ink border border-brand/10 shadow-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-brand/10">
                            <p class="text-sm font-bold truncate"><?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></p>
                            <p class="text-xs text-ink/55 truncate"><?= e($user['email'] ?? '') ?></p>
                        </div>
                        <a href="<?= e(app_url('dashboard')) ?>" class="block px-4 py-2.5 text-sm text-ink/80 hover:bg-cream dark:hover:bg-white/10 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $_icons('user') ?></svg>
                            Mon espace
                        </a>
                        <form method="POST" action="<?= e(app_url('logout')) ?>" class="border-t border-brand/10">
                            <?= CSRF::field() ?>
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-700 hover:bg-red-50 transition flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><?= $_icons('logout') ?></svg>
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <?php if (($flashes = App::flashes())): ?>
        <div class="px-6 pt-4 w-full">
            <?php foreach ($flashes as $type => $message): ?>
                <div data-toast id="toast-<?= e(md5((string) $message)) ?>"
                     class="sb-toast flex items-start justify-between gap-3 px-4 py-3 rounded-xl text-sm font-medium border shadow-lg
                            <?= $type === 'success' ? 'bg-emerald-50 border-emerald-500/30 text-emerald-brand' : 'bg-red-50 border-red-500/30 text-red-700' ?>"
                     role="alert">
                    <span><?= e($message) ?></span>
                    <button type="button" data-dismiss-toast class="shrink-0 opacity-60 hover:opacity-100" aria-label="Fermer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <main class="flex-1 px-4 sm:px-6 py-6">
            <?= $this->yield('content') ?>
        </main>

        <footer class="px-6 py-4 text-xs text-ink/50 border-t border-brand/10 flex items-center justify-between gap-3">
            <span>© <?= date('Y') ?> SeneBridge — Back-office</span>
            <span class="hidden sm:inline">v3.0 — Pro</span>
        </footer>
    </div>
</div>

<!-- Conteneur de toasts JS -->
<div id="sb-toast-stack" class="fixed top-4 right-4 z-50 space-y-2 w-[calc(100%-2rem)] max-w-sm pointer-events-none"></div>

<script src="<?= e(asset_url('assets/js/admin.js')) ?>" defer></script>
<script src="<?= e(asset_url('assets/js/datatable.js')) ?>" defer></script>
<script src="<?= e(asset_url('assets/js/charts.js')) ?>" defer></script>
</body>
</html>
<?php unset($flashes); ?>