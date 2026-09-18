<?php
use App\Support\Router;
use App\Services\WorkflowService;
$this->layout('layouts/client');
$this->section('title'); ?><?= e($project['reference']) ?><?php $this->endSection();
$this->section('content');
$pct = (int) $progress;
?>

<a href="<?= e(Router::url('client.projects')) ?>" class="text-sm text-brand hover:underline">← Retour à mes projets</a>

<div class="bg-white rounded-2xl border border-brand/10 p-6 mt-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-xs text-ink/50"><?= e($project['reference']) ?></p>
            <h1 class="text-2xl font-extrabold mt-1"><?= e($project['name']) ?></h1>
            <p class="text-sm text-ink/60 mt-1">
                <?= e(ucfirst(str_replace('_', ' ', $project['type']))) ?>
                <?= $project['locality'] ? ' · ' . e($project['locality']) : '' ?>
            </p>
            <?php if ($project['description']): ?>
                <p class="text-sm text-ink/60 mt-3 max-w-2xl leading-relaxed"><?= nl2br(e($project['description'])) ?></p>
            <?php endif; ?>
        </div>
        <div class="text-right">
            <span class="px-3 py-1.5 rounded-full text-sm font-semibold
                <?= $project['status'] === 'en_cours' ? 'bg-emerald-50 text-emerald-brand' : ($project['status'] === 'bloque' ? 'bg-red-50 text-red-700' : ($project['status'] === 'termine' ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-ink/60')) ?>">
                <?= e(WorkflowService::formatLabel($project['status'])) ?>
            </span>
            <p class="text-xs text-ink/50 mt-2"><?= e($currentStep) ?></p>
        </div>
    </div>

    <div class="mt-6">
        <div class="flex items-center justify-between text-sm text-ink/60 mb-1">
            <span>Avancement global</span>
            <span class="font-bold text-brand"><?= $pct ?>%</span>
        </div>
        <div class="h-2.5 rounded bg-brand/10">
            <div class="h-2.5 rounded bg-brand transition-all" style="width: <?= $pct ?>%"></div>
        </div>
        <p class="text-xs text-ink/50 mt-1">
            <?= $project['steps_completed'] ?> / <?= $project['steps_count'] ?> étapes terminées
            <?= $project['expected_end_date'] ? ' · Échéance estimée : ' . e(WorkflowService::formatDate($project['expected_end_date'])) : '' ?>
        </p>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2 mt-6">
    <div>
        <h2 class="font-bold text-lg mb-3">Timeline du dossier</h2>
        <?php if ($steps === []): ?>
            <p class="text-sm text-ink/60">Aucune étape définie.</p>
        <?php else: ?>
            <ol class="relative border-l border-brand/15 ml-2 space-y-5">
                <?php foreach ($steps as $step): ?>
                <li class="pl-5 relative">
                    <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full border-2
                        <?= $step['status'] === 'termine' ? 'bg-brand border-brand' : ($step['status'] === 'en_cours' ? 'bg-gold border-gold' : ($step['status'] === 'bloque' ? 'bg-red-500 border-red-500' : 'bg-white border-brand/25')) ?>"></span>
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-semibold text-sm"><?= e($step['name']) ?></p>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            <?= $step['status'] === 'termine' ? 'bg-emerald-50 text-emerald-brand' : ($step['status'] === 'en_cours' ? 'bg-gold/15 text-brand' : ($step['status'] === 'bloque' ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-ink/60')) ?>">
                            <?= e(WorkflowService::formatLabel($step['status'])) ?>
                        </span>
                    </div>
                    <?php if ($step['description']): ?>
                        <p class="text-xs text-ink/60 mt-1"><?= e($step['description']) ?></p>
                    <?php endif; ?>
                    <p class="text-[11px] text-ink/45 mt-1">
                        Étape <?= (int) $step['position'] + 1 ?>
                        <?= $step['due_date'] ? ' · Échéance : ' . e(WorkflowService::formatDate($step['due_date'])) : '' ?>
                        <?= $step['completed_at'] ? ' · Terminée le ' . e(date('d/m/Y', strtotime($step['completed_at']))) : '' ?>
                    </p>
                </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-brand/10 p-5">
            <h2 class="font-bold text-lg mb-3">Votre conseiller</h2>
            <?php if ($project['counselor_id']): ?>
                <p class="text-sm font-semibold"><?= e(trim(($project['counselor_first_name'] ?? '') . ' ' . ($project['counselor_last_name'] ?? ''))) ?></p>
                <p class="text-xs text-ink/60 mt-0.5"><?= e($project['counselor_email'] ?? '') ?></p>
            <?php else: ?>
                <p class="text-sm text-ink/60">Un conseiller sera assigné à votre dossier.</p>
            <?php endif; ?>
        </div>

        <?php if ($properties !== []): ?>
        <div class="bg-white rounded-2xl border border-brand/10 p-5">
            <h2 class="font-bold text-lg mb-3">Biens associés</h2>
            <ul class="divide-y divide-brand/5">
                <?php foreach ($properties as $property): ?>
                <li class="py-2.5 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium"><?= e($property['name']) ?></p>
                        <p class="text-xs text-ink/60"><?= e(ucfirst(str_replace('_', ' ', $property['type']))) ?>
                            <?= $property['locality'] ? ' · ' . e($property['locality']) : '' ?></p>
                    </div>
                    <div class="text-right shrink-0">
                        <?php if ($property['price'] !== null): ?>
                            <p class="text-sm font-bold text-brand"><?= format_number($property['price']) ?> <?= e($property['currency']) ?></p>
                        <?php endif; ?>
                        <p class="text-[11px] text-ink/50"><?= e(WorkflowService::formatLabel($property['status'])) ?></p>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-brand/10 p-5">
            <h2 class="font-bold text-lg mb-3">Détails financiers</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink/50">Budget</dt>
                    <dd class="font-bold"><?= $project['budget'] !== null ? format_number($project['budget']) . ' ' . e($project['currency']) : '—' ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-ink/50">Début</dt>
                    <dd class="font-medium"><?= e(WorkflowService::formatDate($project['start_date'])) ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-ink/50">Échéance</dt>
                    <dd class="font-medium"><?= e(WorkflowService::formatDate($project['expected_end_date'])) ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-ink/50">Créé le</dt>
                    <dd class="font-medium"><?= e(date('d/m/Y', strtotime($project['created_at']))) ?></dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<?php if ($history !== []): ?>
<div class="bg-white rounded-2xl border border-brand/10 p-5 mt-6">
    <h2 class="font-bold text-lg mb-3">Activité récente</h2>
    <ul class="divide-y divide-brand/5">
        <?php foreach ($history as $entry): ?>
        <li class="py-2.5 flex items-start justify-between gap-4 text-sm">
            <div>
                <p class="font-medium">
                    <?= $entry['action'] === 'status'
                        ? 'Statut modifié : <span class="text-gold">' . e(WorkflowService::formatLabel($entry['from_status'] ?? '')) . '</span> → <span class="text-emerald-brand">' . e(WorkflowService::formatLabel($entry['to_status'] ?? '')) . '</span>'
                        : 'Commentaire ajouté' ?>
                </p>
                <?php if (!empty($entry['comment'])): ?>
                    <p class="text-xs text-ink/60 mt-0.5">"<?= e($entry['comment']) ?>"</p>
                <?php endif; ?>
            </div>
            <p class="text-xs text-ink/45 shrink-0">
                <?= e(trim(($entry['first_name'] ?? '') . ' ' . ($entry['last_name'] ?? ''))) ?> ·
                <?= e(date('d/m/Y H:i', strtotime($entry['created_at']))) ?>
            </p>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>