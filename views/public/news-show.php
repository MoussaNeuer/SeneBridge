<?php
use App\Support\Router;
$this->layout('layouts/app');
$this->section('title'); ?><?= e($article['title']) ?> — SeneBridge<?php $this->endSection();
$this->section('content');
?>

<section class="bg-brand text-brand-foreground">
    <div class="max-w-4xl mx-auto px-4 py-12">
        <p class="text-xs opacity-70">Publié le <?= e(date('d/m/Y', strtotime($article['published_at']))) ?></p>
        <h1 class="text-3xl md:text-4xl font-extrabold mt-2"><?= e($article['title']) ?></h1>
        <?php if ($article['excerpt']): ?>
            <p class="text-brand-foreground/80 mt-3 leading-relaxed max-w-2xl"><?= e($article['excerpt']) ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="max-w-4xl mx-auto px-4 py-10">
    <div class="bg-white rounded-2xl border border-brand/10 p-6 md:p-10">
        <div class="prose prose-sm max-w-none text-ink/80 leading-relaxed">
            <?= nl2br(e($article['body'])) ?>
        </div>
    </div>

    <p class="mt-6">
        <a href="<?= e(Router::url('pages.news')) ?>" class="text-brand font-semibold text-sm hover:underline">← Retour aux actualités</a>
    </p>
</div>

<?php if ($others !== []): ?>
<div class="max-w-4xl mx-auto px-4 pb-12">
    <h2 class="font-bold text-lg mb-4">À lire aussi</h2>
    <div class="grid gap-4 sm:grid-cols-2">
        <?php foreach ($others as $other): ?>
        <a href="<?= e(Router::url('pages.news.show', ['slug' => $other['slug']])) ?>"
           class="bg-white rounded-2xl border border-brand/10 p-5 hover:shadow-md transition">
            <h3 class="font-semibold"><?= e($other['title']) ?></h3>
            <?php if ($other['excerpt']): ?>
                <p class="text-xs text-ink/60 mt-1"><?= e($other['excerpt']) ?></p>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>