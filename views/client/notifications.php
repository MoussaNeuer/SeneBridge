<?php
use App\Support\CSRF;
use App\Support\Router;
$this->layout('layouts/client');
$this->section('title'); ?>Notifications<?php $this->endSection();
$this->section('content');
?>

<h1 class="text-2xl font-extrabold mb-6">Notifications</h1>

<?php if ($notifications === []): ?>
    <div class="bg-white rounded-2xl border border-brand/10 p-10 text-center">
        <p class="text-3xl">📭</p>
        <p class="mt-3 text-sm text-ink/60">Aucune notification pour le moment.</p>
    </div>
<?php else: ?>
<div class="bg-white rounded-2xl border border-brand/10 overflow-hidden">
    <ul class="divide-y divide-brand/5">
        <?php foreach ($notifications as $notification): ?>
        <li class="px-5 py-4 flex items-start justify-between gap-4 <?= $notification['is_read'] ? '' : 'bg-gold/5' ?>">
            <div>
                <p class="font-medium <?= $notification['is_read'] ? 'text-ink/70' : 'text-ink' ?>">
                    <?php if (!$notification['is_read']): ?><span class="inline-block w-2 h-2 rounded-full bg-gold mr-2"></span><?php endif; ?>
                    <?= e($notification['title']) ?>
                </p>
                <?php if ($notification['body']): ?>
                    <p class="text-xs text-ink/60 mt-1"><?= nl2br(e($notification['body'])) ?></p>
                <?php endif; ?>
                <p class="text-[11px] text-ink/40 mt-1">
                    <?= e(date('d/m/Y à H:i', strtotime($notification['created_at']))) ?>
                    <?php if ($notification['project_id']): ?>
                        · <a href="<?= e(route('client.projects')) ?>" class="text-brand hover:underline">Dossier lié</a>
                    <?php endif; ?>
                </p>
            </div>
            <?php if (!$notification['is_read']): ?>
                <form method="POST" action="<?= e(Router::url('client.notifications.read', ['publicId' => $notification['public_id']])) ?>">
                    <?= CSRF::field() ?>
                    <button type="submit" class="shrink-0 text-xs px-3 py-1.5 rounded-lg border border-brand/15 hover:bg-cream transition">Marquer lu</button>
                </form>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>