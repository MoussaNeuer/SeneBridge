<?php
use App\Services\WorkflowService;
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Biens immobiliers<?php $this->endSection();
$this->section('content');
$statuses = ['disponible', 'sous_offre', 'reserve', 'vendu', 'loue', 'archive'];
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Biens immobiliers</h1>
        <p class="text-sm text-ink/60 mt-1"><?= (int) $pagination['total'] ?> biens.</p>
    </div>
    <a href="<?= e(Router::url('admin.properties.create')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Nouveau bien</a>
</div>

<form method="GET" action="<?= e(Router::url('admin.properties')) ?>" class="bg-white rounded-2xl border border-brand/10 p-4 mb-6 flex flex-wrap items-end gap-4">
    <div>
        <label for="status" class="text-xs font-medium text-ink/60 block">Statut</label>
        <select id="status" name="status" class="mt-1 px-3 py-2 rounded-lg border border-brand/15 text-sm">
            <option value="">Tous</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status) ?>" <?= ($filter['status'] ?? '') === $status ? 'selected' : '' ?>><?= e(WorkflowService::formatLabel($status)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">Filtrer</button>
</form>

<div class="bg-white rounded-2xl border border-brand/10 overflow-x-auto">
    <table class="w-full text-sm min-w-[720px]">
        <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
            <tr>
                <th class="px-5 py-3">Bien</th>
                <th class="px-5 py-3">Type</th>
                <th class="px-5 py-3 hidden md:table-cell">Localité</th>
                <th class="px-5 py-3">Prix</th>
                <th class="px-5 py-3">Statut</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagination['items'] as $property): ?>
            <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                <td class="px-5 py-3 font-medium"><?= e($property['name']) ?></td>
                <td class="px-5 py-3 text-ink/60"><?= e(ucfirst(str_replace('_', ' ', $property['type']))) ?></td>
                <td class="px-5 py-3 hidden md:table-cell text-ink/60"><?= e($property['locality'] ?: '—') ?></td>
                <td class="px-5 py-3"><?= $property['price'] !== null ? format_number($property['price']) . ' ' . e($property['currency']) : '—' ?></td>
                <td class="px-5 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                        <?= $property['status'] === 'disponible' ? 'bg-emerald-50 text-emerald-brand' : ($property['status'] === 'sous_offre' || $property['status'] === 'reserve' ? 'bg-gold/15 text-brand' : ($property['status'] === 'vendu' || $property['status'] === 'loue' ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-ink/60')) ?>">
                        <?= e(WorkflowService::formatLabel($property['status'])) ?>
                    </span>
                </td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <a href="<?= e(Router::url('admin.properties.edit', ['publicId' => $property['public_id']])) ?>" class="text-xs text-brand font-semibold hover:underline">Modifier</a>
                    <form method="POST" action="<?= e(Router::url('admin.properties.destroy', ['publicId' => $property['public_id']])) ?>" class="inline"
                          onsubmit="return confirm('Supprimer définitivement ce bien ?');">
                        <?= \App\Support\CSRF::field() ?>
                        <button type="submit" class="text-xs text-red-600 font-semibold hover:underline ml-3">Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pagination['items'] === []): ?>
        <p class="text-sm text-ink/60 text-center py-8">Aucun bien trouvé.</p>
    <?php endif; ?>
</div>
<?php $this->include('admin/partials/pagination', ['pagination' => $pagination]); ?>

<?php $this->endSection(); ?>