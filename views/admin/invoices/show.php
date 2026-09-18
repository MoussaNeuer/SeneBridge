<?php
use App\Support\CSRF;
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Facture <?= e($invoice['number']) ?><?php $this->endSection();
$this->section('content');
$statusLabels = ['brouillon' => 'Brouillon', 'envoyee' => 'EnvoyÃ©e', 'partielle' => 'Partielle', 'payee' => 'PayÃ©e', 'en_retard' => 'En retard', 'annulee' => 'AnnulÃ©e'];
$color = static fn (string $s): string => ['brouillon' => 'bg-gray-100 text-ink/60', 'envoyee' => 'bg-gold/15 text-brand', 'partielle' => 'bg-brand/10 text-brand', 'payee' => 'bg-emerald-50 text-emerald-brand', 'en_retard' => 'bg-red-50 text-red-700', 'annulee' => 'bg-gray-100 text-ink/40'][$s] ?? 'bg-gray-100 text-ink/60';
?>

<a href="<?= e(Router::url('admin.invoices')) ?>" class="text-sm text-brand hover:underline">â† Retour aux factures</a>

<div class="bg-white rounded-2xl border border-brand/10 p-6 mt-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-xs text-ink/50">Facture <?= e($invoice['number']) ?></p>
            <h1 class="text-2xl font-extrabold mt-1"><?= format_number($invoice['amount']) ?> <?= e($invoice['currency']) ?></h1>
            <p class="text-sm text-ink/60 mt-1">
                Client : <?= e(trim(($invoice['client_first_name'] ?? '') . ' ' . ($invoice['client_last_name'] ?? ''))) ?>
                <?= $invoice['client_email'] ? ' Â· ' . e($invoice['client_email']) : '' ?>
            </p>
            <?php if ($invoice['project_reference']): ?>
                <p class="text-xs text-ink/50 mt-0.5">Dossier : <?= e($invoice['project_reference']) ?> â€” <?= e($invoice['project_name']) ?></p>
            <?php endif; ?>
        </div>
        <div class="text-right">
            <span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= $color((string) $invoice['status']) ?>">
                <?= e($statusLabels[$invoice['status']] ?? $invoice['status']) ?>
            </span>
        </div>
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
            <dt class="text-xs text-ink/50">Montant payÃ©</dt>
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

<?php if (in_array($invoice['status'], ['brouillon', 'envoyee', 'partielle', 'en_retard'], true)): ?>
<div class="flex flex-wrap gap-3 mt-6">
    <?php if ($invoice['status'] === 'brouillon'): ?>
    <form method="POST" action="<?= e(Router::url('admin.invoices.send', ['publicId' => $invoice['public_id']])) ?>">
        <?= CSRF::field() ?>
        <button type="submit" class="px-5 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">Envoyer au client</button>
    </form>
    <?php endif; ?>
    <form method="POST" action="<?= e(Router::url('admin.invoices.cancel', ['publicId' => $invoice['public_id']])) ?>" onsubmit="return confirm('Annuler cette facture ?')">
        <?= CSRF::field() ?>
        <button type="submit" class="px-5 py-2 rounded-lg border border-red-300 text-red-700 text-sm hover:bg-red-50 transition">Annuler la facture</button>
    </form>
    <a href="<?= e(Router::url('admin.payments.create', ['invoice' => $invoice['public_id']])) ?>"
       class="px-5 py-2 rounded-lg border border-brand/15 text-sm hover:bg-cream transition">Enregistrer un paiement â†’</a>
</div>
<?php endif; ?>

<?php if ($invoice['paid_at']): ?>
    <p class="text-sm mt-4 inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-50 text-emerald-brand">
        EntiÃ¨rement rÃ©glÃ©e le <?= e(date('d/m/Y H:i', strtotime($invoice['paid_at']))) ?>
    </p>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-brand/10 p-5 mt-6">
    <h2 class="font-bold mb-4">Paiements rattachÃ©s (<?= count($payments) ?>)</h2>
    <?php if ($payments === []): ?>
        <p class="text-sm text-ink/60">Aucun paiement enregistrÃ© pour cette facture.</p>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
            <tr>
                <th class="px-4 py-2">RÃ©fÃ©rence</th>
                <th class="px-4 py-2 text-right">Montant</th>
                <th class="px-4 py-2">MÃ©thode</th>
                <th class="px-4 py-2">Statut</th>
                <th class="px-4 py-2 text-right">â†’</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $payment): ?>
            <tr class="border-b border-brand/5 last:border-0">
                <td class="px-4 py-3 font-mono text-xs"><?= e($payment['reference']) ?></td>
                <td class="px-4 py-3 text-right font-semibold"><?= format_number($payment['amount']) ?> <?= e($payment['currency']) ?></td>
                <td class="px-4 py-3"><?= e(\App\Models\Payment::METHOD_LABELS[$payment['method']] ?? $payment['method']) ?></td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                        <?= $payment['status'] === 'valide' ? 'bg-emerald-50 text-emerald-brand' : ($payment['status'] === 'en_cours' ? 'bg-gold/15 text-brand' : 'bg-red-50 text-red-700') ?>">
                        <?= e(\App\Services\WorkflowService::formatLabel((string) $payment['status'])) ?>
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="<?= e(Router::url('admin.payments.show', ['publicId' => $payment['public_id']])) ?>" class="text-xs text-brand hover:underline">Ouvrir â†’</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php $this->endSection(); ?>
<?php unset($statusLabels, $color); ?>