<?php
use App\Support\CSRF;
use App\Support\Router;
use App\Services\WorkflowService;
$this->layout('layouts/admin');
$this->section('title'); ?>Rendez-vous<?php $this->endSection();
$this->section('content');
$colors = ['demande' => 'bg-gold/15 text-brand', 'confirme' => 'bg-emerald-50 text-emerald-brand', 'annule' => 'bg-red-50 text-red-700', 'termine' => 'bg-gray-100 text-ink/60'];
?>

<a href="<?= e(Router::url('admin.appointments')) ?>" class="text-sm text-brand hover:underline">← Retour aux rendez-vous</a>

<div class="bg-white rounded-2xl border border-brand/10 p-6 mt-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs text-ink/50">Rendez-vous</p>
            <h1 class="text-2xl font-extrabold mt-1"><?= e(date('d/m/Y', strtotime($appointment['requested_date']))) ?> à <?= e($appointment['requested_time']) ?></h1>
            <p class="text-sm text-ink/60 mt-1">
                Client : <?= e(trim(($appointment['client_first_name'] ?? '') . ' ' . ($appointment['client_last_name'] ?? ''))) ?>
                <?= $appointment['client_email'] ? ' · ' . e($appointment['client_email']) : '' ?>
            </p>
        </div>
        <span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= $colors[$appointment['status']] ?? 'bg-gray-100' ?>">
            <?= e(WorkflowService::formatLabel((string) $appointment['status'])) ?>
        </span>
    </div>

    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm mt-6">
        <div>
            <dt class="text-xs text-ink/50">Conseiller</dt>
            <dd class="font-medium"><?= e(trim(($appointment['counselor_first_name'] ?? '') . ' ' . ($appointment['counselor_last_name'] ?? ''))) ?> <?= $appointment['counselor_email'] ? e('· ' . $appointment['counselor_email']) : '' ?></dd>
        </div>
        <div>
            <dt class="text-xs text-ink/50">Dossier</dt>
            <dd class="font-medium"><?= $appointment['project_reference'] ? e($appointment['project_reference']) : '—' ?></dd>
        </div>
        <div>
            <dt class="text-xs text-ink/50">Motif</dt>
            <dd class="font-medium"><?= e($appointment['motive']) ?></dd>
        </div>
    </dl>
</div>

<h2 class="font-bold text-lg mt-6 mb-3">Gestion</h2>
<div class="bg-white rounded-2xl border border-brand/10 p-5">
    <div class="flex flex-wrap gap-3">
        <?php if ($appointment['status'] === 'demande'): ?>
        <form method="POST" action="<?= e(Router::url('admin.appointments.confirm', ['publicId' => $appointment['public_id']])) ?>">
            <?= CSRF::field() ?>
            <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-500 transition">Confirmer le rendez-vous</button>
        </form>
        <?php endif; ?>

        <?php if (in_array($appointment['status'], ['confirme'], true)): ?>
        <form method="POST" action="<?= e(Router::url('admin.appointments.complete', ['publicId' => $appointment['public_id']])) ?>">
            <?= CSRF::field() ?>
            <button type="submit" class="px-5 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">Marquer terminé</button>
        </form>
        <?php endif; ?>

        <?php if (in_array($appointment['status'], ['demande', 'confirme'], true)): ?>
        <form method="POST" action="<?= e(Router::url('admin.appointments.cancel', ['publicId' => $appointment['public_id']])) ?>"
              onsubmit="return confirm('Annuler ce rendez-vous ?')">
            <?= CSRF::field() ?>
            <button type="submit" class="px-5 py-2 rounded-lg border border-red-300 text-red-700 text-sm hover:bg-red-50 transition">Annuler le rendez-vous</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection(); ?>
<?php unset($colors); ?>