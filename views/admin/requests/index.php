<?php
use App\Services\WorkflowService;
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Demandes reçues<?php $this->endSection();
$this->section('content');
?>

<div class="flex items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Demandes reçues</h1>
        <p class="text-sm text-ink/60 mt-1"><?= (int) $pagination['total'] ?> demande<?= (int) $pagination['total'] > 1 ? 's' : '' ?>.</p>
    </div>
</div>

<div class="flex flex-wrap gap-2 mb-6">
    <a href="<?= e(Router::url('admin.requests')) ?>" class="px-3 py-1.5 rounded-full text-xs font-semibold border transition <?= ($filter['status'] ?? '') === '' ? 'bg-brand text-brand-foreground' : 'border-brand/15 text-ink/60 hover:bg-cream' ?>">
        Toutes (<?= array_sum($counts) ?>)
    </a>
    <?php foreach ($statuses as $status): ?>
    <a href="<?= e(route('admin.requests') . '?status=' . e($status)) ?>"
       class="px-3 py-1.5 rounded-full text-xs font-semibold border transition <?= ($filter['status'] ?? '') === $status ? 'bg-brand text-brand-foreground' : 'border-brand/15 text-ink/60 hover:bg-cream' ?>">
        <?= e(WorkflowService::formatLabel($status)) ?> (<?= (int) ($counts[$status] ?? 0) ?>)
    </a>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-2xl border border-brand/10 overflow-x-auto">
    <table class="w-full text-sm min-w-[720px]" data-table data-title="demandes">
        <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
            <tr>
                <th class="px-5 py-3">Demande</th>
                <th class="px-5 py-3">Contact</th>
                <th class="px-5 py-3 hidden md:table-cell">Type</th>
                <th class="px-5 py-3">Statut</th>
                <th class="px-5 py-3 hidden lg:table-cell">Reçue le</th>
                <th class="px-5 py-3 text-right">→</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagination['items'] as $request): ?>
            <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                <td class="px-5 py-3 font-medium">
                    <?= e(trim($request['first_name'] . ' ' . $request['last_name'])) ?>
                    <?php if ($request['status'] === 'nouveau'): ?><span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-gold/20 text-brand">NOUVEAU</span><?php endif; ?>
                </td>
                <td class="px-5 py-3 text-ink/70"><?= e($request['email']) ?></td>
                <td class="px-5 py-3 hidden md:table-cell text-ink/60"><?= e(ucfirst(str_replace('_', ' ', $request['project_type']))) ?></td>
                <td class="px-5 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                        <?= $request['status'] === 'nouveau' ? 'bg-gold/15 text-brand' : ($request['status'] === 'qualification' ? 'bg-emerald-50 text-emerald-brand' : ($request['status'] === 'converti' ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-ink/60')) ?>">
                        <?= e(WorkflowService::formatLabel($request['status'])) ?>
                    </span>
                </td>
                <td class="px-5 py-3 hidden lg:table-cell text-ink/60"><?= e(date('d/m/Y', strtotime($request['created_at']))) ?></td>
                <td class="px-5 py-3 text-right">
                    <a href="<?= e(Router::url('admin.requests.show', ['publicId' => $request['public_id']])) ?>" class="text-xs text-brand font-semibold hover:underline">Ouvrir →</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pagination['items'] === []): ?>
        <p class="text-sm text-ink/60 text-center py-8">Aucune demande trouvée.</p>
    <?php endif; ?>
</div>
<?php $this->include('admin/partials/pagination', ['pagination' => $pagination]); ?>

<?php $this->endSection(); ?>