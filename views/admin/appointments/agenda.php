<?php
use App\Support\Router;
use App\Support\CSRF;
$this->layout('layouts/admin');
$this->section('title'); ?>Agenda hebdomadaire<?php $this->endSection();
$this->section('content');
$statusMeta = [
    'demande' => ['label' => 'En attente', 'chip' => 'bg-amber-50 text-amber-700 border-amber-200'],
    'confirme' => ['label' => 'ConfirmÃ©', 'chip' => 'bg-emerald-50 text-emerald-brand border-emerald-200'],
    'annule' => ['label' => 'AnnulÃ©', 'chip' => 'bg-red-50 text-red-700 border-red-200'],
    'termine' => ['label' => 'TerminÃ©', 'chip' => 'bg-brand/10 text-brand border-brand/20'],
];
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Agenda de la semaine</h1>
        <p class="text-sm text-ink/60 mt-1"><?= (int) $stats['confirme'] ?> confirmÃ©s au total Â· <?= (int) $stats['demande'] ?> en attente Â· <?= (int) $stats['aujourdhui'] ?> aujourd'hui.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= e(Router::url('admin.appointments.agenda', ['semaine' => $prev])) ?>" class="px-3 py-2 rounded-lg border border-brand/15 text-sm font-semibold hover:bg-cream transition">&larr; PrÃ©cÃ©dent</a>
        <?php if ($current !== date('Y-m-d', strtotime('monday this week'))): ?>
            <a href="<?= e(Router::url('admin.appointments.agenda')) ?>" class="px-3 py-2 rounded-lg border border-brand/15 text-sm font-semibold hover:bg-cream transition">Cette semaine</a>
        <?php endif; ?>
        <a href="<?= e(Router::url('admin.appointments.agenda', ['semaine' => $next])) ?>" class="px-3 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">Suivant &rarr;</a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-3 items-start">
    <?php foreach ($week as $day => $col): ?>
    <section class="rounded-2xl border <?= $col['is_today'] ? 'border-brand/40 bg-brand/[0.03]' : 'border-brand/10 bg-white' ?> p-3 min-h-[160px]">
        <header class="px-1 pb-2 mb-2 border-b border-brand/10">
            <h2 class="text-xs font-extrabold <?= $col['is_today'] ? 'text-brand' : 'text-ink/70' ?>">
                <?= $col['is_today'] ? 'â— ' : '' ?><?= e($col['label']) ?>
            </h2>
            <p class="text-[11px] text-ink/40 font-medium"><?= count($col['items']) ?> rendez-vous</p>
        </header>

        <div class="space-y-2">
            <?php foreach ($col['items'] as $a): $meta = $statusMeta[$a['status']] ?? $statusMeta['demande']; ?>
            <article class="rounded-xl border border-brand/10 bg-cream/40 p-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-mono text-xs font-bold text-ink/80"><?= e(substr((string) $a['requested_time'], 0, 5)) ?></span>
                    <span class="px-2 py-0.5 rounded-full border text-[10px] font-semibold <?= e($meta['chip']) ?>"><?= e($meta['label']) ?></span>
                </div>

                <p class="mt-1.5 text-sm font-semibold leading-snug">
                    <a href="<?= e(Router::url('admin.appointments.show', ['publicId' => $a['public_id']])) ?>" class="hover:text-brand transition">
                        <?= e($a['client_first_name'] . ' ' . $a['client_last_name']) ?>
                    </a>
                </p>

                <?php if (($a['project_name'] ?? '') !== ''): ?>
                    <p class="text-[11px] text-ink/50 mt-0.5 truncate" title="<?= e($a['project_name']) ?>">
                        <?= e($a['project_reference']) ?> Â· <?= e($a['project_name']) ?>
                    </p>
                <?php endif; ?>

                <?php if (($a['counselor_first_name'] ?? '') !== ''): ?>
                    <p class="text-[11px] text-ink/40 mt-0.5">ðŸ§‘â€ðŸ’¼ <?= e($a['counselor_first_name'] . ' ' . $a['counselor_last_name']) ?></p>
                <?php endif; ?>

                <div class="mt-2 flex flex-wrap gap-1.5">
                    <?php if ($a['status'] === 'demande'): ?>
                        <form action="<?= e(Router::url('admin.appointments.confirm', ['publicId' => $a['public_id']])) ?>" method="POST" data-async class="inline"><?= CSRF::field() ?>
                            <button type="submit" class="px-2 py-1 rounded-md bg-emerald-50 text-emerald-brand text-[11px] font-semibold hover:bg-emerald-100 transition">Confirmer</button>
                        </form>
                        <form action="<?= e(Router::url('admin.appointments.cancel', ['publicId' => $a['public_id']])) ?>" method="POST" class="inline"><?= CSRF::field() ?>
                            <button type="submit" data-confirm="Annuler ce rendez-vous ?" class="px-2 py-1 rounded-md bg-red-50 text-red-700 text-[11px] font-semibold hover:bg-red-100 transition">Annuler</button>
                        </form>
                    <?php elseif ($a['status'] === 'confirme'): ?>
                        <form action="<?= e(Router::url('admin.appointments.complete', ['publicId' => $a['public_id']])) ?>" method="POST" data-async class="inline"><?= CSRF::field() ?>
                            <button type="submit" class="px-2 py-1 rounded-md bg-brand/10 text-brand text-[11px] font-semibold hover:bg-brand/20 transition">Terminer</button>
                        </form>
                        <form action="<?= e(Router::url('admin.appointments.cancel', ['publicId' => $a['public_id']])) ?>" method="POST" class="inline"><?= CSRF::field() ?>
                            <button type="submit" data-confirm="Annuler ce rendez-vous ?" class="px-2 py-1 rounded-md bg-red-50 text-red-700 text-[11px] font-semibold hover:bg-red-100 transition">Annuler</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>

            <?php if ($col['items'] === []): ?>
                <p class="text-[11px] text-ink/40 text-center py-4 border border-dashed border-brand/20 rounded-xl">Rien de prÃ©vu.</p>
            <?php endif; ?>
        </div>
    </section>
    <?php endforeach; ?>
</div>
<?php $this->endSection(); ?>