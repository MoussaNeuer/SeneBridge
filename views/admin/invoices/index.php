<?php
use App\Support\Router;
use App\Services\WorkflowService;
$this->layout('layouts/admin');
$this->section('title'); ?>Factures<?php $this->endSection();
$this->section('content');
$statusLabels = ['brouillon' => 'Brouillon', 'envoyee' => 'Envoyée', 'partielle' => 'Partielle', 'payee' => 'Payée', 'en_retard' => 'En retard', 'annulee' => 'Annulée'];
$color = static fn (string $s): string => ['brouillon' => 'bg-gray-100 text-ink/60', 'envoyee' => 'bg-gold/15 text-brand', 'partielle' => 'bg-brand/10 text-brand', 'payee' => 'bg-emerald-50 text-emerald-brand', 'en_retard' => 'bg-red-50 text-red-700', 'annulee' => 'bg-gray-100 text-ink/40'][$s] ?? 'bg-gray-100 text-ink/60';
$filters = ['status' => (string) ($filters['status'] ?? '')];
?>
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Factures</h1>
        <p class="text-sm text-ink/60 mt-1">Suivi des facturations et règlements clients.</p>
    </div>
    <a href="<?= e(Router::url('admin.invoices.create')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Nouvelle facture</a>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Brouillons</p>
        <p class="text-2xl font-extrabold text-ink mt-1"><?= (int) ($stats['brouillon'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Envoyées</p>
        <p class="text-2xl font-extrabold text-gold mt-1"><?= (int) ($stats['envoyee'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Partielles</p>
        <p class="text-2xl font-extrabold text-brand mt-1"><?= (int) ($stats['partielle'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">À recouvrer (envoyées + partielles + retard)</p>
        <p class="text-2xl font-extrabold text-red-700 mt-1"><?= (int) ($stats['a_recouvrer'] ?? 0) ?></p>
        <a href="<?= e(Router::url('admin.payments.create')) ?>" class="text-xs text-brand hover:underline">Enregistrer un paiement →</a>
    </div>
</div>

<div class="bg-white rounded-2xl border border-brand/10 overflow-hidden">
    <div class="px-5 py-4 border-b border-brand/10 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-bold">Toutes les factures</h2>
        <form method="GET" class="flex gap-2 text-sm">
            <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-brand/15">
                <option value="">Tous les statuts</option>
                <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php if ($pagination['items'] === []): ?>
        <p class="px-5 py-10 text-center text-sm text-ink/60">Aucune facture trouvée.</p>
    <?php else: ?>
    <table class="w-full text-sm" data-table data-title="factures">
        <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
            <tr>
                <th class="px-5 py-3">N°</th>
                <th class="px-5 py-3 hidden md:table-cell">Client</th>
                <th class="px-5 py-3 text-right">Montant</th>
                <th class="px-5 py-3 text-right hidden lg:table-cell">Réglé</th>
                <th class="px-5 py-3">Statut</th>
                <th class="px-5 py-3 text-right">→</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagination['items'] as $invoice): ?>
            <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                <td class="px-5 py-3 font-mono text-xs"><?= e($invoice['number']) ?></td>
                <td class="px-5 py-3 hidden md:table-cell text-ink/70"><?= e(trim(($invoice['client_first_name'] ?? '') . ' ' . ($invoice['client_last_name'] ?? ''))) ?: '—' ?></td>
                <td class="px-5 py-3 text-right font-semibold"><?= format_number($invoice['amount']) ?> <?= e($invoice['currency']) ?></td>
                <td class="px-5 py-3 text-right hidden lg:table-cell text-ink/60"><?= format_number($invoice['paid_amount']) ?></td>
                <td class="px-5 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $color((string) $invoice['status']) ?>">
                        <?= e($statusLabels[$invoice['status']] ?? $invoice['status']) ?>
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="<?= e(Router::url('admin.invoices.show', ['publicId' => $invoice['public_id']])) ?>" class="text-xs text-brand font-semibold hover:underline">Ouvrir →</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php if ($pagination['last_page'] > 1): ?>
<div class="flex items-center gap-2 mt-4 text-sm">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
        <a href="<?= e(Router::url('admin.invoices', ['page' => $i] + ($filters['status'] !== '' ? ['status' => $filters['status']] : []))) ?>"
           class="px-3 py-1.5 rounded-lg border <?= $pagination['page'] === $i ? 'bg-brand text-brand-foreground border-brand' : 'border-brand/15 hover:bg-cream' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>
<?php unset($statusLabels, $color); ?>