<?php
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Paiements<?php $this->endSection();
$this->section('content');
$colors = ['en_cours' => 'bg-gold/15 text-brand', 'valide' => 'bg-emerald-50 text-emerald-brand', 'rejete' => 'bg-red-50 text-red-700', 'rembourse' => 'bg-gray-100 text-ink/60'];
$filters = ['status' => (string) ($filters['status'] ?? '')];
?>
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Paiements</h1>
        <p class="text-sm text-ink/60 mt-1">Enregistrement et validation des rÃ¨glements.</p>
    </div>
    <a href="<?= e(Router::url('admin.payments.create')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Enregistrer un paiement</a>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Ã€ valider</p>
        <p class="text-2xl font-extrabold text-gold mt-1"><?= (int) ($stats['en_cours'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">ValidÃ©s</p>
        <p class="text-2xl font-extrabold text-emerald-brand mt-1"><?= (int) ($stats['valide'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">RejetÃ©s</p>
        <p class="text-2xl font-extrabold text-red-700 mt-1"><?= (int) ($stats['rejete'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Montant validÃ© (cumul)</p>
        <p class="text-2xl font-extrabold text-brand mt-1"><?= format_number($stats['montant_valide']) ?></p>
    </div>
</div>

<div class="bg-white rounded-2xl border border-brand/10 overflow-hidden">
    <div class="px-5 py-4 border-b border-brand/10 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-bold">Tous les paiements</h2>
        <form method="GET" class="flex gap-2 text-sm">
            <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-brand/15">
                <option value="">Tous les statuts</option>
                <?php foreach ($colors as $key => $unused): ?>
                <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>>
                    <?= e(\App\Services\WorkflowService::formatLabel($key)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php if ($pagination['items'] === []): ?>
        <p class="px-5 py-10 text-center text-sm text-ink/60">Aucun paiement trouvÃ©.</p>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
            <tr>
                <th class="px-5 py-3">RÃ©fÃ©rence</th>
                <th class="px-5 py-3 hidden md:table-cell">Client</th>
                <th class="px-5 py-3 text-right">Montant</th>
                <th class="px-5 py-3 hidden lg:table-cell">MÃ©thode</th>
                <th class="px-5 py-3">Statut</th>
                <th class="px-5 py-3 text-right">â†’</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagination['items'] as $payment): ?>
            <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                <td class="px-5 py-3 font-mono text-xs"><?= e($payment['reference']) ?></td>
                <td class="px-5 py-3 hidden md:table-cell text-ink/70"><?= e(trim(($payment['client_first_name'] ?? '') . ' ' . ($payment['client_last_name'] ?? ''))) ?: 'â€”' ?></td>
                <td class="px-5 py-3 text-right font-semibold"><?= format_number($payment['amount']) ?> <?= e($payment['currency']) ?></td>
                <td class="px-5 py-3 hidden lg:table-cell text-ink/60"><?= e(\App\Models\Payment::METHOD_LABELS[$payment['method']] ?? $payment['method']) ?></td>
                <td class="px-5 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $colors[$payment['status']] ?? 'bg-gray-100' ?>">
                        <?= e(\App\Services\WorkflowService::formatLabel((string) $payment['status'])) ?>
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="<?= e(Router::url('admin.payments.show', ['publicId' => $payment['public_id']])) ?>" class="text-xs text-brand font-semibold hover:underline">Ouvrir â†’</a>
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
        <a href="<?= e(Router::url('admin.payments', ['page' => $i] + ($filters['status'] !== '' ? ['status' => $filters['status']] : []))) ?>"
           class="px-3 py-1.5 rounded-lg border <?= $pagination['page'] === $i ? 'bg-brand text-brand-foreground border-brand' : 'border-brand/15 hover:bg-cream' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>
<?php unset($colors); ?>