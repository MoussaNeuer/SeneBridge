<?php
use App\Services\WorkflowService;
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Projets / Dossiers<?php $this->endSection();
$this->section('content');
$statuses = ['en_cours' => 'En cours', 'bloque' => 'Bloqué', 'termine' => 'Terminé', 'archive' => 'Archivé'];
$types = ['immobilier' => 'Immobilier', 'gestion_projets' => 'Gestion de projets', 'import_export' => 'Import/Export', 'auto' => 'Auto', 'conciergerie' => 'Conciergerie', 'investissement' => 'Investissement'];
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Dossiers projets</h1>
        <p class="text-sm text-ink/60 mt-1"><?= (int) $stats['total'] ?> au total · <?= (int) $stats['en_cours'] ?> en cours.</p>
    </div>
    <a href="<?= e(Router::url('admin.projects.create')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Nouveau dossier</a>
</div>

<form method="GET" action="<?= e(Router::url('admin.projects')) ?>" class="bg-white rounded-2xl border border-brand/10 p-4 mb-6 flex flex-wrap items-end gap-4">
    <div>
        <label for="status" class="text-xs font-medium text-ink/60">Statut</label>
        <select id="status" name="status" class="mt-1 px-3 py-2 rounded-lg border border-brand/15 text-sm">
            <option value="">Tous les statuts</option>
            <?php foreach ($statuses as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="type" class="text-xs font-medium text-ink/60">Type</label>
        <select id="type" name="type" class="mt-1 px-3 py-2 rounded-lg border border-brand/15 text-sm">
            <option value="">Tous les types</option>
            <?php foreach ($types as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($filters['type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">Filtrer</button>
</form>

<div class="bg-white rounded-2xl border border-brand/10 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[720px]">
            <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
                <tr>
                    <th class="px-5 py-3">Référence</th>
                    <th class="px-5 py-3">Projet</th>
                    <th class="px-5 py-3 hidden md:table-cell">Type</th>
                    <th class="px-5 py-3">Statut</th>
                    <th class="px-5 py-3 hidden lg:table-cell">Créé le</th>
                    <th class="px-5 py-3 text-right">→</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagination['items'] as $project): ?>
                <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                    <td class="px-5 py-3 font-mono text-xs"><?= e($project['reference']) ?></td>
                    <td class="px-5 py-3 font-medium"><?= e($project['name']) ?></td>
                    <td class="px-5 py-3 hidden md:table-cell text-ink/60"><?= e(ucfirst(str_replace('_', ' ', $project['type']))) ?></td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            <?= $project['status'] === 'en_cours' ? 'bg-emerald-50 text-emerald-brand' : ($project['status'] === 'bloque' ? 'bg-red-50 text-red-700' : ($project['status'] === 'termine' ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-ink/60')) ?>">
                            <?= e(WorkflowService::formatLabel($project['status'])) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 hidden lg:table-cell text-ink/60"><?= e(date('d/m/Y', strtotime($project['created_at']))) ?></td>
                    <td class="px-5 py-3 text-right">
                        <a href="<?= e(Router::url('admin.projects.show', ['publicId' => $project['public_id']])) ?>" class="text-xs text-brand font-semibold hover:underline">Ouvrir →</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pagination['items'] === []): ?>
        <p class="text-sm text-ink/60 text-center py-8">Aucun dossier trouvé.</p>
    <?php endif; ?>
</div>
<?php $this->include('admin/partials/pagination', ['pagination' => $pagination]); ?>

<?php $this->endSection(); ?>