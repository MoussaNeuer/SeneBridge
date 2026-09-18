<?php
use App\Support\Router;
use App\Services\WorkflowService;
$this->layout('layouts/admin');
$this->section('title'); ?>Rendez-vous<?php $this->endSection();
$this->section('content');
$colors = ['demande' => 'bg-gold/15 text-brand', 'confirme' => 'bg-emerald-50 text-emerald-brand', 'annule' => 'bg-red-50 text-red-700', 'termine' => 'bg-gray-100 text-ink/60'];
$filters = ['status' => (string) ($filters['status'] ?? '')];
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Rendez-vous</h1>
        <p class="text-sm text-ink/60 mt-1">Demandes des clients et suivi des entretiens.</p>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Demandes en attente</p>
        <p class="text-2xl font-extrabold text-gold mt-1"><?= (int) ($stats['demande'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Confirmés</p>
        <p class="text-2xl font-extrabold text-emerald-brand mt-1"><?= (int) ($stats['confirme'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Aujourd'hui</p>
        <p class="text-2xl font-extrabold text-brand mt-1"><?= (int) ($stats['aujourdhui'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Terminés</p>
        <p class="text-2xl font-extrabold text-ink mt-1"><?= (int) ($stats['termine'] ?? 0) ?></p>
    </div>
</div>

<div class="bg-white rounded-2xl border border-brand/10 overflow-hidden">
    <div class="px-5 py-4 border-b border-brand/10 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-bold">Tous les rendez-vous</h2>
        <form method="GET" class="flex gap-2 text-sm">
            <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-brand/15">
                <option value="">Tous les statuts</option>
                <?php foreach ($colors as $key => $unused): ?>
                <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e(WorkflowService::formatLabel($key)) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php if ($pagination['items'] === []): ?>
        <p class="px-5 py-10 text-center text-sm text-ink/60">Aucun rendez-vous trouvé.</p>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
            <tr>
                <th class="px-5 py-3">Date</th>
                <th class="px-5 py-3 hidden md:table-cell">Client</th>
                <th class="px-5 py-3 hidden lg:table-cell">Conseiller</th>
                <th class="px-5 py-3">Motif</th>
                <th class="px-5 py-3">Statut</th>
                <th class="px-5 py-3 text-right">→</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagination['items'] as $appointment): ?>
            <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                <td class="px-5 py-3">
                    <span class="font-medium"><?= e(date('d/m/Y', strtotime($appointment['requested_date']))) ?></span>
                    <span class="text-ink/50">à <?= e($appointment['requested_time']) ?></span>
                </td>
                <td class="px-5 py-3 hidden md:table-cell text-ink/70"><?= e(trim(($appointment['client_first_name'] ?? '') . ' ' . ($appointment['client_last_name'] ?? ''))) ?></td>
                <td class="px-5 py-3 hidden lg:table-cell text-ink/70"><?= e(trim(($appointment['counselor_first_name'] ?? '') . ' ' . ($appointment['counselor_last_name'] ?? ''))) ?: '—' ?></td>
                <td class="px-5 py-3 text-ink/60 max-w-[200px] truncate"><?= e($appointment['motive']) ?></td>
                <td class="px-5 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $colors[$appointment['status']] ?? 'bg-gray-100' ?>">
                        <?= e(WorkflowService::formatLabel((string) $appointment['status'])) ?>
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="<?= e(Router::url('admin.appointments.show', ['publicId' => $appointment['public_id']])) ?>" class="text-xs text-brand font-semibold hover:underline">Ouvrir →</a>
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
        <a href="<?= e(Router::url('admin.appointments', ['page' => $i] + ($filters['status'] !== '' ? ['status' => $filters['status']] : []))) ?>"
           class="px-3 py-1.5 rounded-lg border <?= $pagination['page'] === $i ? 'bg-brand text-brand-foreground border-brand' : 'border-brand/15 hover:bg-cream' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>
<?php unset($colors); ?>