<?php
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Messagerie<?php $this->endSection();
$this->section('content');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Messagerie clients</h1>
        <p class="text-sm text-ink/60 mt-1">Une conversation par dossier — échanges avec les clients en temps réel.</p>
    </div>
    <?php if ($unreadCount > 0): ?>
        <span class="px-3 py-1.5 rounded-full bg-gold/15 text-brand text-sm font-semibold"><?= (int) $unreadCount ?> non lu(s)</span>
    <?php endif; ?>
</div>

<div class="bg-white rounded-2xl border border-brand/10 overflow-hidden">
    <?php if ($conversations === []): ?>
        <p class="px-5 py-12 text-center text-sm text-ink/60">Aucune conversation active pour le moment.</p>
    <?php else: ?>
    <ul class="divide-y divide-brand/5">
        <?php foreach ($conversations as $conversation): ?>
        <li class="px-5 py-4 flex items-center justify-between gap-4 hover:bg-cream/60 transition">
            <a href="<?= e(Router::url('admin.messages.show', ['publicId' => $conversation['public_id']])) ?>" class="flex-1 min-w-0 flex items-center gap-4">
                <div class="min-w-0">
                    <p class="font-semibold text-sm truncate">
                        <?= $conversation['last_at'] !== null ? e(trim(($conversation['client_first_name'] ?? '') . ' ' . ($conversation['client_last_name'] ?? ''))) : 'Nouvel échange' ?>
                        <?php if ((int) $conversation['unread'] > 0): ?>
                            <span class="ml-2 inline-block min-w-5 h-5 px-1.5 rounded-full bg-gold text-brand text-[10px] font-bold grid place-items-center"><?= (int) $conversation['unread'] ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="text-xs text-ink/60 truncate mt-0.5">
                        <?= $conversation['last_body'] ? e(\App\Support\Str::limit((string) $conversation['last_body'], 90)) : 'Aucun message…' ?>
                    </p>
                    <p class="text-[11px] text-ink/40 mt-0.5">
                        <?= $conversation['project_reference'] ? e($conversation['project_reference']) . ' — ' : '' ?><?= e($conversation['title'] ?? 'Conversation') ?>
                        <?= $conversation['last_at'] ? ' · ' . e(date('d/m/Y H:i', strtotime($conversation['last_at']))) : '' ?>
                    </p>
                </div>
            </a>
            <a href="<?= e(Router::url('admin.messages.show', ['publicId' => $conversation['public_id']])) ?>" class="shrink-0 text-xs text-brand font-semibold hover:underline">Ouvrir →</a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<?php $this->endSection(); ?>