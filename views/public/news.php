<?php
use App\Support\Router;
$this->layout('layouts/app');
$this->section('title'); ?>Actualités — SeneBridge<?php $this->endSection();
$this->section('content');
?>

<section class="bg-brand text-brand-foreground">
    <div class="max-w-7xl mx-auto px-4 py-14 text-center">
        <p class="text-sm tracking-widest uppercase opacity-70">Le magazine</p>
        <h1 class="text-3xl md:text-5xl font-extrabold mt-2">Actualités & conseils</h1>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 py-12">
    <?php if ($articles === []): ?>
        <p class="text-center text-sm text-ink/60">Aucune publication pour le moment.</p>
    <?php else: ?>
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($articles as $article): ?>
        <a href="<?= e(Router::url('pages.news.show', ['slug' => $article['slug']])) ?>"
           class="bg-white rounded-2xl border border-brand/10 p-6 hover:shadow-lg transition flex flex-col">
            <p class="text-xs text-ink/45">Publié le <?= e(date('d/m/Y', strtotime($article['published_at']))) ?></p>
            <h2 class="text-lg font-bold mt-2 group-hover:text-brand"><?= e($article['title']) ?></h2>
            <?php if ($article['excerpt']): ?>
                <p class="text-sm text-ink/65 mt-2 leading-relaxed flex-1"><?= e($article['excerpt']) ?></p>
            <?php endif; ?>
            <span class="mt-4 text-brand font-semibold text-sm">Lire l'article →</span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php $this->endSection(); ?>