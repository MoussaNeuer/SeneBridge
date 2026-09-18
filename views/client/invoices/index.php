<?php
use App\Support\Router;
$this->layout('layouts/client');
$this->section('title'); ?>Mes factures<?php $this->endSection();
$this->section('content');
$statusLabels = ['brouillon' => 'Brouillon', 'envoyee' => 'Envoyée', 'partielle' => 'Partielle', 'payee' => 'Payée', 'en_retard' => 'En retard', 'annulee' => 'Annulée'];
$colors = ['brouillon' => 'bg-gray-100 text-ink/60', 'envoyee' => 'bg-gold/15 text-brand', 'partielle' => 'bg-brand/10 text-brand', 'payee' => 'bg-emerald-50 text-emerald-brand', 'en_retard' => 'bg-red-50 text-red-700', 'annulee' => 'bg-gray-100 text-ink/40'];
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Mes factures</h1>
        <p class="text-sm text-ink/60 mt-1">Suivez vos facturations en toute transparence.</p>
    </div>
</div>

<?php if ($invoices === []): ?>
<div class="bg-white rounded-2xl border border-brand/10 p-10 text-center">
    <p class="text-3xl">🧾</p>
    <p class="mt-3 text-sm text-ink/60">Aucune facture pour le moment.</p>
</div>
<?php else: ?>
<div class="bg-white rounded-2xl border border-brand/10 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
            <tr>
                <th class="px-5 py-3">N°</th>
                <th class="px-5 py-3 hidden md:table-cell">Dossier</th>
                <th class="px-5 py-3 text-right">Montant</th>
                <th class="px-5 py-3 text-right hidden sm:table-cell">Réglé</th>
                <th class="px-5 py-3">Statut</th>
                <th class="px-5 py-3 text-right">→</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $invoice): ?>
            <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                <td class="px-5 py-3 font-mono text-xs"><?= e($invoice['number']) ?></td>
                <td class="px-5 py-3 hidden md:table-cell text-ink/60"><?= e($invoice['project_reference'] ?? '—') ?></td>
                <td class="px-5 py-3 text-right font-semibold"><?= format_number($invoice['amount']) ?> <?= e($invoice['currency']) ?></td>
                <td class="px-5 py-3 text-right hidden sm:table-cell text-ink/60"><?= format_number($invoice['paid_amount']) ?></td>
                <td class="px-5 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $colors[$invoice['status']] ?? 'bg-gray-100' ?>">
                        <?= e($statusLabels[$invoice['status']] ?? $invoice['status']) ?>
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="<?= e(Router::url('client.invoices.show', ['publicId' => $invoice['public_id']])) ?>"
                       class="text-xs text-brand font-semibold hover:underline">Voir →</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($unpaidCount > 0): ?>
<div class="mt-6 bg-white rounded-2xl border border-brand/10 p-5">
    <p class="text-sm text-ink/70">
        💡 <span class="font-semibold"><?= (int) $unpaidCount ?> facture(s)</span> en attente de règlement.
        Pour payer (Wave Business, virement ou espèces), prenez contact via la
        <a href="<?= e(Router::url('client.messages')) ?>" class="text-brand hover:underline font-medium">messagerie</a> ou téléphoner à votre conseiller.
    </p>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>
<?php unset($statusLabels, $colors); ?>