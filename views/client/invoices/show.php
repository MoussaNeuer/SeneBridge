<?php
use App\Support\Router;
$this->layout('layouts/client');
$this->section('title'); ?>Facture <?= e($invoice['number']) ?><?php $this->endSection();
$this->section('content');
$statusLabels = ['brouillon' => 'Brouillon', 'envoyee' => 'EnvoyÃ©e', 'partielle' => 'Partielle', 'payee' => 'PayÃ©e', 'en_retard' => 'En retard', 'annulee' => 'AnnulÃ©e'];
$colors = ['brouillon' => 'bg-gray-100 text-ink/60', 'envoyee' => 'bg-gold/15 text-brand', 'partielle' => 'bg-brand/10 text-brand', 'payee' => 'bg-emerald-50 text-emerald-brand', 'en_retard' => 'bg-red-50 text-red-700', 'annulee' => 'bg-gray-100 text-ink/40'];
?>

<a href="<?= e(Router::url('client.invoices')) ?>" class="text-sm text-brand hover:underline">â† Retour Ã  mes factures</a>

<div class="bg-white rounded-2xl border border-brand/10 p-6 mt-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-xs text-ink/50">Facture <?= e($invoice['number']) ?></p>
            <h1 class="text-2xl font-extrabold mt-1"><?= format_number($invoice['amount']) ?> <?= e($invoice['currency']) ?></h1>
            <?php if ($invoice['project_reference']): ?>
                <p class="text-xs text-ink/50 mt-1">Dossier : <?= e($invoice['project_reference']) ?> â€” <?= e($invoice['project_name']) ?></p>
            <?php endif; ?>
        </div>
        <span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= $colors[$invoice['status']] ?? 'bg-gray-100' ?>">
            <?= e($statusLabels[$invoice['status']] ?? $invoice['status']) ?>
        </span>
    </div>

    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mt-6">
        <div>
            <dt class="text-xs text-ink/50">Ã‰mise le</dt>
            <dd class="font-medium"><?= e(date('d/m/Y', strtotime($invoice['issue_date']))) ?></dd>
        </div>
        <div>
            <dt class="text-xs text-ink/50">Ã‰chÃ©ance</dt>
            <dd class="font-medium"><?= $invoice['due_date'] ? e(date('d/m/Y', strtotime($invoice['due_date']))) : 'â€”' ?></dd>
        </div>
        <div>
            <dt class="text-xs text-ink/50">DÃ©jÃ  rÃ©glÃ©</dt>
            <dd class="font-semibold"><?= format_number($invoice['paid_amount']) ?> <?= e($invoice['currency']) ?></dd>
        </div>
        <div>
            <dt class="text-xs text-ink/50">Reste dÃ»</dt>
            <dd class="font-semibold"><?= format_number((float) $invoice['amount'] - (float) $invoice['paid_amount']) ?> <?= e($invoice['currency']) ?></dd>
        </div>
    </dl>

    <?php if ($invoice['description']): ?>
        <p class="text-sm text-ink/60 mt-5 leading-relaxed"><?= nl2br(e($invoice['description'])) ?></p>
    <?php endif; ?>
</div>

<div class="bg-white rounded-2xl border border-brand/10 p-5 mt-6">
    <h2 class="font-bold mb-4">Paiements enregistrÃ©s (<?= count($payments) ?>)</h2>
    <?php if ($payments === []): ?>
        <p class="text-sm text-ink/60">Aucun paiement enregistrÃ© pour cette facture.</p>
    <?php else: ?>
    <ul class="divide-y divide-brand/5">
        <?php foreach ($payments as $payment): ?>
        <li class="py-3 flex items-center justify-between gap-3 text-sm">
            <div>
                <p class="font-medium"><?= e($payment['reference']) ?> Â· <?= e(\App\Models\Payment::METHOD_LABELS[$payment['method']] ?? $payment['method']) ?></p>
                <p class="text-xs text-ink/60"><?= e(date('d/m/Y', strtotime($payment['payment_date']))) ?></p>
            </div>
            <div class="text-right">
                <p class="font-semibold"><?= format_number($payment['amount']) ?> <?= e($payment['currency']) ?></p>
                <span class="text-[11px] px-2 py-0.5 rounded-full font-semibold
                    <?= $payment['status'] === 'valide' ? 'bg-emerald-50 text-emerald-brand' : ($payment['status'] === 'en_cours' ? 'bg-gold/15 text-brand' : 'bg-red-50 text-red-700') ?>">
                    <?= e(\App\Services\WorkflowService::formatLabel((string) $payment['status'])) ?>
                </span>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<?php if ($canPay): ?>
<div class="mt-6 bg-white rounded-2xl border border-brand/10 p-5">
    <p class="text-sm text-ink/70">
        ðŸ’³ <span class="font-semibold">Comment rÃ©gler ?</span> Effectuez le paiement via Wave Business
        (transfÃ©rez le montant indiquÃ© en prÃ©cisant votre nom et le numÃ©ro de facture), par virement bancaire ou en espÃ¨ces.
        Envoyez ensuite votre reÃ§u via la <a href="<?= e(Router::url('client.messages')) ?>" class="text-brand hover:underline font-medium">messagerie</a>
        et prÃ©cisez le montant : notre Ã©quipe l'enregistrera et validera votre rÃ¨glement.
    </p>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>
<?php unset($statusLabels, $colors); ?>