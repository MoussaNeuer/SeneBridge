<?php
use App\Support\Router;
$this->layout('layouts/client');
$this->section('title'); ?>Messagerie<?php $this->endSection();
$this->section('content');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Messagerie</h1>
        <p class="text-sm text-ink/60 mt-1">Échangez directement avec l'équipe SeneBridge sur vos dossiers.</p>
    </div>
    <?php if ($unreadCount > 0): ?>
        <span class="px-3 py-1.5 rounded-full bg-gold/15 text-brand text-sm font-semibold"><?= (int) $unreadCount ?> nouveau(x) message(s)</span>
    <?php endif; ?>
</div>

<?php if ($conversations === []): ?>
<div class="bg-white rounded-2xl border border-brand/10 p-10 text-center">
    <p class="text-3xl">💬</p>
    <p class="mt-3 text-sm text-ink/60">
        Aucune conversation pour le moment. Votre fil de discussion s'ouvre automatiquement
        sur chacun de vos dossiers dès le premier échange.
    </p>
</div>
<?php else: ?>
<div class="bg-white rounded-2xl border border-brand/10 overflow-hidden">
    <ul class="divide-y divide-brand/5">
        <?php foreach ($conversations as $conversation): ?>
        <li class="px-5 py-4 hover:bg-cream/60 transition">
            <a href="<?= e(Router::url('client.messages.show', ['publicId' => $conversation['public_id']])) ?>" class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-semibold text-sm truncate">
                        <?= e($conversation['title'] ?? 'Conversation') ?>
                        <?php if ($conversation['project_reference']): ?>
                            <span class="ml-1 text-ink/50 font-normal">— <?= e($conversation['project_reference']) ?></span>
                        <?php endif; ?>
                        <?php if ((int) $conversation['unread'] > 0): ?>
                            <span class="ml-2 inline-block min-w-5 h-5 px-1.5 rounded-full bg-gold text-brand text-[10px] font-bold grid place-items-center"><?= (int) $conversation['unread'] ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="text-xs text-ink/60 truncate mt-0.5">
                        <?= $conversation['last_body'] ? e(\App\Support\Str::limit((string) $conversation['last_body'], 90)) : 'Aucun message…' ?>
                    </p>
                    <p class="text-[11px] text-ink/40 mt-0.5"><?= $conversation['last_at'] ? 'Dernier échange ' . e(date('d/m/Y H:i', strtotime($conversation['last_at']))) : '' ?></p>
                </div>
                <span class="shrink-0 text-xs text-brand font-semibold">Ouvrir →</span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>