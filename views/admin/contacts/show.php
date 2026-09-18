<?php
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Message de <?= e($message['name']) ?><?php $this->endSection();
$this->section('content');
?>

<a href="<?= e(Router::url('admin.contacts')) ?>" class="text-sm text-brand hover:underline">← Retour aux messages</a>

<div class="bg-white rounded-2xl border border-brand/10 p-6 mt-4 max-w-3xl">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-extrabold"><?= e($message['subject']) ?></h1>
        <span class="px-2 py-1 rounded-full text-xs <?= $message['is_read'] ? 'bg-gray-100 text-ink/60' : 'bg-gold/15 text-brand' ?>">
            <?= $message['is_read'] ? 'Lu' : 'Non lu' ?>
        </span>
    </div>
    <p class="text-sm text-ink/60 mt-1">De : <?= e($message['name']) ?> — <?= e($message['email']) ?>
        <?= $message['phone'] ? ' — ☎ ' . e($message['phone']) : '' ?></p>
    <p class="text-xs text-ink/45 mt-1">Reçu le <?= e(date('d/m/Y à H:i', strtotime($message['created_at']))) ?></p>
    <div class="mt-5 bg-cream rounded-xl p-5 text-sm leading-relaxed whitespace-pre-line">
        <?= nl2br(e($message['message'])) ?>
    </div>
</div>

<?php $this->endSection(); ?>