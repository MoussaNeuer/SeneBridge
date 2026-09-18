<?php
use App\Support\Router;
$this->layout('layouts/client');
$this->section('title'); ?>Mes rendez-vous<?php $this->endSection();
$this->section('content');
$colors = ['demande' => 'bg-gold/15 text-brand', 'confirme' => 'bg-emerald-50 text-emerald-brand', 'annule' => 'bg-red-50 text-red-700', 'termine' => 'bg-gray-100 text-ink/60'];
?>

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Mes rendez-vous</h1>
        <p class="text-sm text-ink/60 mt-1">Demandez un entretien avec l'Ã©quipe SeneBridge.</p>
    </div>
    <a href="<?= e(Router::url('client.appointments.create')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Demander un rendez-vous</a>
</div>

<?php if ($appointments === []): ?>
<div class="bg-white rounded-2xl border border-brand/10 p-10 text-center">
    <p class="text-3xl">ðŸ“…</p>
    <p class="mt-3 text-sm text-ink/60">Aucun rendez-vous pour le moment. Faites votre premiÃ¨re demande.</p>
</div>
<?php else: ?>
<div class="bg-white rounded-2xl border border-brand/10 overflow-hidden">
    <ul class="divide-y divide-brand/5">
        <?php foreach ($appointments as $appointment): ?>
        <li class="px-5 py-4 flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="font-semibold text-sm">
                    <?= e(date('d/m/Y', strtotime($appointment['requested_date']))) ?> Ã  <?= e($appointment['requested_time']) ?>
                </p>
                <p class="text-xs text-ink/60 mt-0.5"><?= e($appointment['motive']) ?></p>
                <?php if ($appointment['counselor_first_name']): ?>
                    <p class="text-[11px] text-ink/50 mt-0.5">avec <?= e(trim($appointment['counselor_first_name'] . ' ' . $appointment['counselor_last_name'])) ?></p>
                <?php endif; ?>
            </div>
            <div class="text-right shrink-0">
                <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $colors[$appointment['status']] ?? 'bg-gray-100' ?>">
                    <?= e(\App\Services\WorkflowService::formatLabel((string) $appointment['status'])) ?>
                </span>
                <?php if ($appointment['status'] === 'demande'): ?>
                <form method="POST" action="<?= e(Router::url('client.appointments.cancel', ['publicId' => $appointment['public_id']])) ?>" class="mt-2"
                      onsubmit="return confirm('Annuler cette demande de rendez-vous ?')">
                    <?= \App\Support\CSRF::field() ?>
                    <button type="submit" class="text-xs text-red-700 hover:underline">Annuler la demande</button>
                </form>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>
<?php unset($colors); ?>