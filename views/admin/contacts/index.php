<?php
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Messages contact<?php $this->endSection();
$this->section('content');
?>

<h1 class="text-2xl font-extrabold mb-6">Messages de contact</h1>

<div class="bg-white rounded-2xl border border-brand/10 overflow-x-auto">
    <table class="w-full text-sm min-w-[720px]">
        <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
            <tr>
                <th class="px-5 py-3">Expéditeur</th>
                <th class="px-5 py-3">Sujet</th>
                <th class="px-5 py-3">Reçu le</th>
                <th class="px-5 py-3">Statut</th>
                <th class="px-5 py-3 text-right">→</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagination['items'] as $message): ?>
            <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50 <?= $message['is_read'] ? '' : 'font-semibold' ?>">
                <td class="px-5 py-3"><?= e($message['name']) ?><span class="block text-xs font-normal text-ink/50"><?= e($message['email']) ?></span></td>
                <td class="px-5 py-3"><?= e($message['subject']) ?></td>
                <td class="px-5 py-3 text-ink/60"><?= e(date('d/m/Y H:i', strtotime($message['created_at']))) ?></td>
                <td class="px-5 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-normal <?= $message['is_read'] ? 'bg-gray-100 text-ink/60' : 'bg-gold/15 text-brand' ?>">
                        <?= $message['is_read'] ? 'Lu' : 'Non lu' ?>
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="<?= e(Router::url('admin.contacts.show', ['publicId' => $message['public_id']])) ?>" class="text-xs text-brand font-semibold hover:underline">Lire →</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pagination['items'] === []): ?>
        <p class="text-sm text-ink/60 text-center py-8">Aucun message reçu.</p>
    <?php endif; ?>
</div>
<?php $this->include('admin/partials/pagination', ['pagination' => $pagination]); ?>

<?php $this->endSection(); ?>