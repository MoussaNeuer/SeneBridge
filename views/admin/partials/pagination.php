<?php
/**
 * Partiel de pagination. Attend : $pagination (page/last_page/total) et $qs (string de requête existant).
 * Les liens conservent les filtres existants (?status=...) et ajoutent page=N.
 */
$page = (int) ($pagination['page'] ?? 1);
$lastPage = (int) ($pagination['last_page'] ?? 1);
if ($lastPage <= 1):
    return;
endif;
$baseUrl = $_SERVER['REQUEST_URI'] ?? '/';
$baseUrl = preg_replace('/([?&])page=\d+/', '', $baseUrl);
if (str_contains($baseUrl, '?')) {
    $sep = '&';
} else {
    $baseUrl .= '?';
    $sep = '';
}
?>
<nav class="flex items-center justify-between gap-4 mt-6 text-sm">
    <p class="text-ink/60">Page <span class="font-semibold"><?= $page ?></span> / <?= $lastPage ?> · <?= (int) ($pagination['total'] ?? 0) ?> résultat<?= (int) ($pagination['total'] ?? 0) > 1 ? 's' : '' ?></p>
    <div class="flex items-center gap-2">
        <?php if ($page > 1): ?>
            <a href="<?= e($baseUrl . $sep . 'page=' . ($page - 1)) ?>" class="px-3 py-1.5 rounded-lg border border-brand/15 hover:bg-cream transition">← Précédent</a>
        <?php endif; ?>
        <?php if ($page < $lastPage): ?>
            <a href="<?= e($baseUrl . $sep . 'page=' . ($page + 1)) ?>" class="px-3 py-1.5 rounded-lg border border-brand/15 hover:bg-cream transition">Suivant →</a>
        <?php endif; ?>
    </div>
</nav>