<?php
use App\Support\Router;
use App\Services\WorkflowService;
$this->layout('layouts/client');
$this->section('title'); ?>Mes projets<?php $this->endSection();
$this->section('content');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Mes projets</h1>
        <p class="text-sm text-ink/60 mt-1"><?= count($projects) ?> dossier<?= count($projects) > 1 ? 's' : '' ?> au total.</p>
    </div>
    <a href="<?= e(route('pages.start-project')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Nouveau projet</a>
</div>

<?php if ($projects === []): ?>
    <div class="bg-white rounded-2xl border border-brand/10 p-10 text-center">
        <p class="text-3xl">📂</p>
        <p class="mt-3 text-sm text-ink/60">Vous n'avez pas encore de dossier. <a href="<?= e(route('pages.start-project')) ?>" class="text-brand font-semibold hover:underline">Démarrez-en un</a>.</p>
    </div>
<?php else: ?>

<div class="grid gap-5 lg:grid-cols-2">
    <?php foreach ($projects as $project): ?>
    <?php $pct = (int) ($progress[(int) $project['id']] ?? 0); ?>
    <a href="<?= e(Router::url('client.projects.show', ['publicId' => $project['public_id']])) ?>"
       class="bg-white rounded-2xl border border-brand/10 p-5 hover:shadow-md transition group">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="font-mono text-[11px] text-ink/50"><?= e($project['reference']) ?></p>
                <h2 class="font-bold mt-0.5 group-hover:text-brand transition"><?= e($project['name']) ?></h2>
                <p class="text-xs text-ink/60 mt-1">
                    <?= e(ucfirst(str_replace('_', ' ', $project['type']))) ?>
                    <?= $project['locality'] ? ' — ' . e($project['locality']) : '' ?>
                </p>
            </div>
            <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold
                <?= $project['status'] === 'en_cours' ? 'bg-emerald-50 text-emerald-brand' : ($project['status'] === 'bloque' ? 'bg-red-50 text-red-700' : ($project['status'] === 'termine' ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-ink/60')) ?>">
                <?= e(WorkflowService::formatLabel($project['status'])) ?>
            </span>
        </div>
        <div class="mt-4">
            <div class="flex items-center justify-between text-xs text-ink/60 mb-1">
                <span>Avancement</span><span><?= $pct ?>%</span>
            </div>
            <div class="h-2 rounded bg-brand/10">
                <div class="h-2 rounded bg-brand transition-all" style="width: <?= $pct ?>%"></div>
            </div>
        </div>
        <p class="mt-4 text-xs text-ink/50">
            Créé le <?= e(date('d/m/Y', strtotime($project['created_at']))) ?>
            · Budget <?= $project['budget'] !== null ? format_number($project['budget']) . ' ' . e($project['currency']) : 'n/a' ?>
        </p>
    </a>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php $this->endSection(); ?>