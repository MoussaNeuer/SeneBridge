<?php
use App\Support\CSRF;
use App\Support\Router;
use App\Support\Gate;
$this->layout('layouts/admin');
$this->section('title'); ?>Conversation<?php $this->endSection();
$this->section('content');
$conv = $conversation;
$clientName = trim(($conv['client_first_name'] ?? '') . ' ' . ($conv['client_last_name'] ?? ''));
?>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-1 bg-white rounded-2xl border border-brand/10 p-4 overflow-y-auto max-h-[70vh]">
        <h2 class="font-bold text-sm mb-3 px-1">Conversations</h2>
        <ul class="space-y-1">
            <?php foreach ($conversations as $item): ?>
            <li>
                <a href="<?= e(Router::url('admin.messages.show', ['publicId' => $item['public_id']])) ?>"
                   class="block px-3 py-2 rounded-lg text-sm <?= $item['public_id'] === $conv['public_id'] ? 'bg-brand/10 text-brand font-semibold' : 'hover:bg-cream' ?>">
                    <span class="block truncate"><?= e(trim(($item['client_first_name'] ?? '') . ' ' . ($item['client_last_name'] ?? ''))) ?: 'Nouveau' ?></span>
                    <?php if ($item['project_reference']): ?>
                        <span class="block text-[11px] text-ink/50"><?= e($item['project_reference']) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="lg:col-span-2 bg-white rounded-2xl border border-brand/10 flex flex-col min-h-[70vh]">
        <div class="px-5 py-4 border-b border-brand/10">
            <h1 class="font-bold"><?= e($clientName ?: 'Client') ?></h1>
            <p class="text-xs text-ink/60 mt-0.5">
                <?= $conv['project_reference'] ? 'Dossier ' . e($conv['project_reference']) . ' — ' : '' ?><?= e($conv['project_name'] ?? '') ?>
                <?= $conv['client_email'] ? ' · ' . e($conv['client_email']) : '' ?>
            </p>
        </div>

        <div class="flex-1 px-5 py-4 space-y-4 overflow-y-auto max-h-[52vh]" id="thread">
            <?php if ($messages === []): ?>
                <p class="text-center text-sm text-ink/50 pt-6">Démarrez la conversation avec votre client.</p>
            <?php endif; ?>
            <?php foreach ($messages as $message): ?>
            <?php $mine = (int) $message['sender_id'] === (int) $user['id']; ?>
            <?php $isStaff = (int) $message['role_id'] > 0 && !Gate::isRole($message, 'client'); ?>
            <div class="flex <?= $mine ? 'justify-end' : 'justify-start' ?>">
                <div class="max-w-[75%] px-4 py-2.5 rounded-2xl text-sm <?= $mine ? 'bg-brand text-brand-foreground' : 'bg-cream border border-brand/10' ?>">
                    <?php if (!$mine): ?>
                        <span class="block text-[11px] font-semibold text-ink/50 mb-0.5">
                            <?= e(trim(($message['first_name'] ?? '') . ' ' . ($message['last_name'] ?? ''))) ?> <?= $isStaff ? '· Équipe' : '' ?>
                        </span>
                    <?php endif; ?>
                    <p class="whitespace-pre-wrap"><?= nl2br(e($message['body'])) ?></p>
                    <span class="block text-[10px] mt-1 opacity-60"><?= e(date('d/m/Y H:i', strtotime($message['created_at']))) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" action="<?= e(Router::url('admin.messages.store', ['publicId' => $conv['public_id']])) ?>" class="border-t border-brand/10 p-4 flex gap-3">
            <?= CSRF::field() ?>
            <textarea name="body" rows="2" required placeholder="Votre message…" class="flex-1 px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30"></textarea>
            <button type="submit" class="px-5 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition self-end">Envoyer</button>
        </form>
    </div>
</div>

<?php if ($conv['status'] === 'ouverte'): ?>
<form method="POST" action="<?= e(Router::url('admin.messages.close', ['publicId' => $conv['public_id']])) ?>" class="mt-6">
    <?= CSRF::field() ?>
    <button type="submit" class="px-4 py-2 rounded-lg border border-brand/15 text-sm hover:bg-cream transition">Archiver la conversation</button>
</form>
<?php endif; ?>

<?php $this->endSection(); ?>