<?php
use App\Support\CSRF;
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Paiement <?= e($payment['reference']) ?><?php $this->endSection();
$this->section('content');
$colors = ['en_cours' => 'bg-gold/15 text-brand', 'valide' => 'bg-emerald-50 text-emerald-brand', 'rejete' => 'bg-red-50 text-red-700', 'rembourse' => 'bg-gray-100 text-ink/60'];
?>

<a href="<?= e(Router::url('admin.payments')) ?>" class="text-sm text-brand hover:underline">â† Retour aux paiements</a>

<div class="bg-white rounded-2xl border border-brand/10 p-6 mt-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-xs text-ink/50">Paiement <?= e($payment['reference']) ?></p>
            <h1 class="text-2xl font-extrabold mt-1"><?= format_number($payment['amount']) ?> <?= e($payment['currency']) ?></h1>
            <p class="text-sm text-ink/60 mt-1">
                Client : <?= e(trim(($payment['client_first_name'] ?? '') . ' ' . ($payment['client_last_name'] ?? ''))) ?>
                <?= $payment['client_email'] ? ' Â· ' . e($payment['client_email']) : '' ?>
            </p>
        </div>
        <span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= $colors[$payment['status']] ?? 'bg-gray-100' ?>">
            <?= e(\App\Services\WorkflowService::formatLabel((string) $payment['status'])) ?>
        </span>
    </div>

    <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 text-sm mt-6">
        <div>
            <dt class="text-xs text-ink/50">PayÃ© le</dt>
            <dd class="font-medium"><?= e(date('d/m/Y', strtotime($payment['payment_date']))) ?></dd>
        </div>
        <div>
            <dt class="text-xs text-ink/50">MÃ©thode</dt>
            <dd class="font-medium"><?= e(\App\Models\Payment::METHOD_LABELS[$payment['method']] ?? $payment['method']) ?></dd>
        </div>
        <div>
            <dt class="text-xs text-ink/50">RÃ©fÃ©rence</dt>
            <dd class="font-mono text-xs"><?= e($payment['reference'] ?: 'â€”') ?></dd>
        </div>
        <div>
            <dt class="text-xs text-ink/50">Facture</dt>
            <dd class="font-medium"><?= $payment['invoice_number'] ? e($payment['invoice_number']) : 'â€”' ?></dd>
        </div>
        <div>
            <dt class="text-xs text-ink/50">Dossier</dt>
            <dd class="font-medium"><?= $payment['project_reference'] ? e($payment['project_reference']) : 'â€”' ?></dd>
        </div>
    </dl>

    <?php if ($payment['notes']): ?>
        <p class="text-sm text-ink/60 mt-5"><?= nl2br(e($payment['notes'])) ?></p>
    <?php endif; ?>

    <?php if ($payment['recorded_first_name']): ?>
        <p class="text-xs text-ink/45 mt-4">EnregistrÃ© par <?= e(trim($payment['recorded_first_name'] . ' ' . $payment['recorded_last_name'])) ?>
            <?= $payment['validated_at'] ? ' Â· ValidÃ© le ' . e(date('d/m/Y H:i', strtotime($payment['validated_at']))) : '' ?></p>
    <?php endif; ?>
</div>

<?php if ($payment['receipt_internal_name']): ?>
<div class="bg-white rounded-2xl border border-brand/10 p-5 mt-6 flex items-center justify-between">
    <div>
        <p class="font-semibold text-sm">ReÃ§u de paiement</p>
        <p class="text-xs text-ink/60 mt-0.5">Preuve jointe lors de l'enregistrement.</p>
    </div>
    <a href="<?= e(Router::url('admin.payments.receipt', ['publicId' => $payment['public_id']])) ?>"
       class="px-4 py-2 rounded-lg border border-brand/15 text-sm hover:bg-cream transition">TÃ©lÃ©charger â†“</a>
</div>
<?php endif; ?>

<?php if ($payment['status'] === 'en_cours'): ?>
<div class="flex flex-wrap gap-3 mt-6">
    <form method="POST" action="<?= e(Router::url('admin.payments.validate', ['publicId' => $payment['public_id']])) ?>">
        <?= CSRF::field() ?>
        <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-500 transition">Valider le paiement</button>
    </form>
    <form method="POST" action="<?= e(Router::url('admin.payments.reject', ['publicId' => $payment['public_id']])) ?>"
          onsubmit="return confirm('Rejeter ce paiement ?')">
        <?= CSRF::field() ?>
        <button type="submit" class="px-5 py-2 rounded-lg border border-red-300 text-red-700 text-sm hover:bg-red-50 transition">Rejeter</button>
    </form>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>
<?php unset($colors); ?>