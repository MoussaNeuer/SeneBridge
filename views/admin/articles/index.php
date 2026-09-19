<?php
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Actualités<?php $this->endSection();
$this->section('content');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Actualités</h1>
        <p class="text-sm text-ink/60 mt-1"><?= (int) $pagination['total'] ?> article<?= (int) $pagination['total'] > 1 ? 's' : '' ?>.</p>
    </div>
    <a href="<?= e(Router::url('admin.articles.create')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Nouvel article</a>
</div>

<div class="bg-white rounded-2xl border border-brand/10 overflow-x-auto">
    <table class="w-full text-sm min-w-[720px]" data-table data-title="articles">
        <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
            <tr>
                <th class="px-5 py-3">Titre</th>
                <th class="px-5 py-3 hidden md:table-cell">Slug</th>
                <th class="px-5 py-3">Statut</th>
                <th class="px-5 py-3 hidden lg:table-cell">Publié le</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagination['items'] as $article): ?>
            <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                <td class="px-5 py-3 font-medium"><?= e($article['title']) ?></td>
                <td class="px-5 py-3 hidden md:table-cell text-ink/60 font-mono text-xs"><?= e($article['slug']) ?></td>
                <td class="px-5 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $article['status'] === 'publie' ? 'bg-emerald-50 text-emerald-brand' : 'bg-gray-100 text-ink/60' ?>">
                        <?= $article['status'] === 'publie' ? 'Publié' : 'Brouillon' ?>
                    </span>
                </td>
                <td class="px-5 py-3 hidden lg:table-cell text-ink/60"><?= $article['published_at'] ? e(date('d/m/Y', strtotime($article['published_at']))) : '—' ?></td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <a href="<?= e(Router::url('admin.articles.edit', ['publicId' => $article['public_id']])) ?>" class="text-xs text-brand font-semibold hover:underline">Modifier</a>
                    <form method="POST" action="<?= e(Router::url('admin.articles.toggle', ['publicId' => $article['public_id']])) ?>" class="inline ml-3">
                        <?= \App\Support\CSRF::field() ?>
                        <button type="submit" class="text-xs font-semibold hover:underline <?= $article['status'] === 'publie' ? 'text-red-600' : 'text-emerald-brand' ?>">
                            <?= $article['status'] === 'publie' ? 'Dépublier' : 'Publier' ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pagination['items'] === []): ?>
        <p class="text-sm text-ink/60 text-center py-8">Aucun article.</p>
    <?php endif; ?>
</div>
<?php $this->include('admin/partials/pagination', ['pagination' => $pagination]); ?>

<?php $this->endSection(); ?>