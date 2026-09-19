<?php
use App\Support\Gate;
use App\Support\CSRF;
use App\Support\Router;
use App\Services\WorkflowService;
$this->layout('layouts/admin');
$this->section('title'); ?>Client : <?= e($client['first_name'] . ' ' . $client['last_name']) ?><?php $this->endSection();
$this->section('content');
?>

<a href="<?= e(Router::url('admin.clients')) ?>" class="text-sm text-brand hover:underline">← Retour aux clients</a>

<div class="bg-white rounded-2xl border border-brand/10 p-6 mt-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold"><?= e(trim($client['first_name'] . ' ' . $client['last_name'])) ?></h1>
            <p class="text-sm text-ink/60 mt-1"><?= e($client['email']) ?>
                <?= empty($client['email_verified_at']) ? '· <span class="text-amber-600">non vérifiée</span>' : '' ?>
            </p>
            <p class="text-xs text-ink/50 mt-2">
                <?= $client['phone'] ? '☎ ' . e($client['phone']) : '' ?>
                <?= $client['locality'] ? ' · 📍 ' . e($client['locality']) : '' ?>
            </p>
        </div>
        <a href="<?= e(Router::url('admin.clients.projects.create', ['publicId' => $client['public_id']])) ?>"
           class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Ouvrir un dossier</a>
    </div>
</div>

<?php if (Gate::allows('roles.manage') && (int) $client['id'] !== (int) $user['id']): ?>
<div class="bg-white rounded-2xl border border-brand/10 p-5 mt-6">
    <h2 class="font-bold mb-3">Rôle et accès</h2>
    <form method="POST" action="<?= e(Router::url('admin.users.role', ['publicId' => $client['public_id']])) ?>" class="flex flex-wrap items-end gap-3">
        <?= CSRF::field() ?>
        <div class="flex-1 min-w-[220px]">
            <label for="role_select" class="text-sm font-medium block">Rôle actuel : <span class="font-semibold text-brand"><?= e(Gate::roleName($client) ?: '—') ?></span></label>
            <select id="role_select" name="role_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <?php foreach ($roles as $role): ?>
                <option value="<?= (int) $role['id'] ?>" <?= (int) $role['id'] === (int) $client['role_id'] ? 'selected' : '' ?>>
                    <?= e($role['label']) ?> (<?= e($role['name']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <p class="text-[11px] text-ink/50 mt-1">Changer le rôle agit sur les permissions du compte dès la prochaine requête.</p>
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">Enregistrer</button>
    </form>
</div>
<?php endif; ?>

<div class="grid gap-6 lg:grid-cols-2 mt-6">
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <h2 class="font-bold mb-3">Dossiers (<?= count($projects) ?>)</h2>
        <?php if ($projects === []): ?>
            <p class="text-sm text-ink/60">Aucun dossier.</p>
        <?php else: ?>
        <ul class="divide-y divide-brand/5">
            <?php foreach ($projects as $project): ?>
            <?php $p = ($progress[(int) $project['id']] ?? ['steps_count' => 0, 'steps_completed' => 0]); ?>
            <?php $pct = $p['steps_count'] > 0 ? (int) round($p['steps_completed'] * 100 / $p['steps_count']) : 0; ?>
            <li class="py-3 flex items-center justify-between gap-3">
                <div>
                    <p class="font-mono text-[11px] text-ink/50"><?= e($project['reference']) ?></p>
                    <a href="<?= e(Router::url('admin.projects.show', ['publicId' => $project['public_id']])) ?>" class="text-sm font-medium hover:text-brand"><?= e($project['name']) ?></a>
                    <p class="text-[11px] text-ink/50 mt-0.5"><?= e(WorkflowService::formatLabel($project['status'])) ?> · <?= $pct ?>%</p>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-semibold
                    <?= $project['status'] === 'en_cours' ? 'bg-emerald-50 text-emerald-brand' : ($project['status'] === 'bloque' ? 'bg-red-50 text-red-700' : ($project['status'] === 'termine' ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-ink/60')) ?>">
                    <?= e(WorkflowService::formatLabel($project['status'])) ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <h2 class="font-bold mb-3">Demandes reçues</h2>
        <?php if ($requests === []): ?>
            <p class="text-sm text-ink/60">Aucune demande trouvée.</p>
        <?php else: ?>
        <ul class="divide-y divide-brand/5">
            <?php foreach ($requests as $request): ?>
            <li class="py-3">
                <a href="<?= e(Router::url('admin.requests.show', ['publicId' => $request['public_id']])) ?>" class="text-sm font-medium hover:text-brand">
                    <?= e(ucfirst(str_replace('_', ' ', $request['project_type']))) ?>
                </a>
                <p class="text-[11px] text-ink/50 mt-0.5"><?= e(WorkflowService::formatLabel($request['status'])) ?> · <?= e(date('d/m/Y', strtotime($request['created_at']))) ?></p>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection(); ?>