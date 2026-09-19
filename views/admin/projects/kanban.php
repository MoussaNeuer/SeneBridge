<?php
use App\Support\Router;
use App\Support\CSRF;
$this->layout('layouts/admin');
$this->section('title'); ?>Pipeline / Kanban<?php $this->endSection();
$this->section('content');
$columnMeta = [
    'en_cours' => ['label' => 'En cours', 'dot' => 'bg-emerald-brand', 'ring' => 'border-emerald-200', 'head' => 'text-emerald-brand'],
    'bloque' => ['label' => 'Bloqué', 'dot' => 'bg-red-500', 'ring' => 'border-red-200', 'head' => 'text-red-600'],
    'termine' => ['label' => 'Terminé', 'dot' => 'bg-brand', 'ring' => 'border-brand/20', 'head' => 'text-brand'],
];
$columns = $statuses;
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Pipeline de dossiers</h1>
        <p class="text-sm text-ink/60 mt-1"><?= (int) $stats['total'] ?> dossiers · <?= (int) $stats['en_cours'] ?> en cours · <?= (int) $stats['bloque'] ?> bloqués · <?= (int) $stats['termine'] ?> terminés.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= e(Router::url('admin.projects')) ?>?status=archive" class="text-sm text-ink/60 hover:text-brand transition">Archives</a>
        <a href="<?= e(Router::url('admin.projects.create')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Nouveau dossier</a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
    <?php foreach ($columns as $status): $meta = $columnMeta[$status]; $items = $boards[$status] ?? []; ?>
    <section class="rounded-2xl border border-brand/10 bg-cream/40 p-3">
        <header class="flex items-center justify-between px-2 py-2 mb-3">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full <?= e($meta['dot']) ?>"></span>
                <h2 class="text-sm font-bold <?= e($meta['head']) ?>"><?= e($meta['label']) ?></h2>
            </div>
            <span class="px-2 py-0.5 rounded-full bg-white border border-brand/10 text-xs font-semibold text-ink/60"><?= count($items) ?></span>
        </header>

        <div class="space-y-3">
            <?php foreach ($items as $project): $total = max(1, (int) $project['steps_count']); $done = (int) $project['steps_completed']; $pct = (int) round($done / $total * 100); ?>
            <article class="bg-white rounded-xl border border-brand/10 p-4 shadow-sm hover:border-brand/30 transition group">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2 font-mono text-[11px] text-ink/50">
                        <?= e($project['reference']) ?>
                    </div>
                    <span class="text-[11px] text-ink/40 font-medium"><?= $pct ?>%</span>
                </div>

                <h3 class="mt-2 text-sm font-bold leading-snug group-hover:text-brand transition">
                    <a href="<?= e(Router::url('admin.projects.show', ['publicId' => $project['public_id']])) ?>"><?= e($project['name']) ?></a>
                </h3>

                <p class="mt-1 text-xs text-ink/60 truncate"><?= e(ucfirst(str_replace('_', ' ', $project['type']))) ?>
                    <?= $project['locality'] ? '· ' . e($project['locality']) : '' ?>
                </p>

                <p class="mt-2 text-xs text-ink/60">
                    👤 <?= e($project['client_first_name'] . ' ' . $project['client_last_name']) ?>
                    <?php if ($project['counselor_first_name']): ?>
                        <span class="text-ink/40">· conseiller <?= e($project['counselor_first_name'] . ' ' . $project['counselor_last_name']) ?></span>
                    <?php endif; ?>
                </p>

                <div class="mt-3 h-1.5 rounded-full bg-cream overflow-hidden">
                    <div class="h-full rounded-full bg-brand transition-all" style="width: <?= $pct ?>%"></div>
                </div>

                <?php if ($user): $can = (\App\Policies\ProjectPolicy::manage($user, $project)); endif; ?>
                <?php if ($can): ?>
                <form action="<?= e(Router::url('admin.projects.status', ['publicId' => $project['public_id']])) ?>" method="POST" class="mt-3 flex items-center gap-2"><?= CSRF::field() ?>
                    <select name="status" data-status-root class="flex-1 px-2 py-1.5 rounded-lg border border-brand/15 text-xs font-medium bg-white" aria-label="Changer le statut">
                        <?php foreach ($columnMeta as $opt => $om): ?>
                            <option value="<?= e($opt) ?>" <?= $opt === $project['status'] ? 'selected' : '' ?>><?= e($om['label']) ?></option>
                        <?php endforeach; ?>
                        <option value="archive" <?= $project['status'] === 'archive' ? 'selected' : '' ?>>Archivé</option>
                    </select>
                    <input type="hidden" name="from" value="kanban">
                    <button type="submit" data-async class="px-2.5 py-1.5 rounded-lg bg-brand text-brand-foreground text-xs font-semibold hover:bg-brand-dark transition">OK</button>
                </form>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>

            <?php if ($items === []): ?>
                <p class="text-xs text-ink/40 text-center py-6 border border-dashed border-brand/20 rounded-xl bg-white/60">Aucun dossier dans cette colonne.</p>
            <?php endif; ?>
        </div>
    </section>
    <?php endforeach; ?>
</div>
<?php $this->endSection(); ?>