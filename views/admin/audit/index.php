<?php
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Journal d'audit<?php $this->endSection();
$this->section('content');
$entities = [
    'projects' => 'Dossier', 'project_steps' => 'Étape', 'invoices' => 'Facture', 'payments' => 'Paiement',
    'appointments' => 'Rendez-vous', 'users' => 'Utilisateur', 'documents' => 'Document', 'media' => 'Média',
    'roles' => 'Rôle', 'permissions' => 'Permission', 'contacts' => 'Contact', 'requests' => 'Demande',
    'articles' => 'Actualité', 'notifications' => 'Notification',
];
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Journal d'audit</h1>
        <p class="text-sm text-ink/60 mt-1">Traçabilité des actions sensibles · <?= (int) $pagination['total'] ?> entrées.</p>
    </div>
</div>

<?php if ($actions !== []): ?>
<form method="GET" action="<?= e(Router::url('admin.audit')) ?>" class="bg-white rounded-2xl border border-brand/10 p-4 mb-6 flex flex-wrap items-end gap-4">
    <div>
        <label for="action" class="text-xs font-medium text-ink/60">Action</label>
        <select id="action" name="action" class="mt-1 px-3 py-2 rounded-lg border border-brand/15 text-sm">
            <option value="">Toutes les actions</option>
            <?php foreach ($actions as $action): ?>
                <option value="<?= e($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= e($action) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="q" class="text-xs font-medium text-ink/60">Recherche</label>
        <input id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Action, utilisateur, entité…" class="mt-1 px-3 py-2 rounded-lg border border-brand/15 text-sm w-64">
    </div>
    <button type="submit" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">Filtrer</button>
    <?php if ($filters['action'] !== '' || $filters['q'] !== ''): ?>
        <a href="<?= e(Router::url('admin.audit')) ?>" class="px-4 py-2 rounded-lg border border-brand/15 text-sm font-semibold hover:bg-cream transition">Réinitialiser</a>
    <?php endif; ?>
</form>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-brand/10 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[760px]" data-table data-title="journal">
            <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
                <tr>
                    <th class="px-5 py-3">Heure</th>
                    <th class="px-5 py-3">Utilisateur</th>
                    <th class="px-5 py-3">Action</th>
                    <th class="px-5 py-3">Entité</th>
                    <th class="px-5 py-3 hidden md:table-cell">Détail</th>
                    <th class="px-5 py-3 hidden lg:table-cell">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagination['items'] as $entry): ?>
                <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                    <td class="px-5 py-3 text-ink/60 whitespace-nowrap"><?= e(date('d/m/Y H:i', strtotime($entry['created_at']))) ?></td>
                    <td class="px-5 py-3 font-medium"><?= e(trim(($entry['first_name'] ?? '') . ' ' . ($entry['last_name'] ?? '')) ?: 'Système') ?></td>
                    <td class="px-5 py-3"><code class="px-2 py-0.5 rounded bg-cream text-xs font-mono"><?= e($entry['action']) ?></code></td>
                    <td class="px-5 py-3 text-ink/60">
                        <?= e($entities[$entry['entity_type']] ?? ucfirst(preg_replace('/_/', ' ', (string) $entry['entity_type']))) ?>
                        <?php if ($entry['entity_id']): ?> <span class="text-ink/40">#<?= e($entry['entity_id']) ?></span><?php endif; ?>
                    </td>
                    <td class="px-5 py-3 hidden md:table-cell">
                        <?php if ($entry['new_values']): ?>
                            <details class="group">
                                <summary class="cursor-pointer text-xs text-brand font-medium hover:underline list-none">Afficher la trace</summary>
                                <pre class="mt-2 text-[11px] bg-cream rounded-lg p-2 overflow-auto max-h-40"><?= e($entry['new_values']) ?></pre>
                            </details>
                        <?php else: ?>
                            <span class="text-ink/40">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 hidden lg:table-cell"><code class="text-[11px] text-ink/50"><?= e($entry['ip_address']) ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pagination['items'] === []): ?>
        <p class="text-sm text-ink/60 text-center py-8">Aucune entrée enregistrée.</p>
    <?php endif; ?>
</div>
<?php $this->include('admin/partials/pagination', ['pagination' => $pagination]); ?>

<?php $this->endSection(); ?>