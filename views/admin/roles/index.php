<?php
use App\Support\CSRF;
use App\Support\Router;
use App\Support\Gate;
$this->layout('layouts/admin');
$this->section('title'); ?>Rôles & droits<?php $this->endSection();
$this->section('content');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Rôles &amp; droits</h1>
        <p class="text-sm text-ink/60 mt-1"><?= count($roles) ?> rôle<?= count($roles) > 1 ? 's' : '' ?> · les permissions des rôles non-système sont modifiables ici.</p>
    </div>
    <?php if (Gate::allows('roles.manage')): ?>
    <a href="<?= e(Router::url('admin.roles.create')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Nouveau rôle</a>
    <?php endif; ?>
</div>

<div class="bg-white rounded-2xl border border-brand/10 overflow-x-auto">
    <table class="w-full text-sm min-w-[720px]">
        <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
            <tr>
                <th class="px-5 py-3">Rôle</th>
                <th class="px-5 py-3">Description</th>
                <th class="px-5 py-3">Utilisateurs</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role): ?>
            <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                <td class="px-5 py-3 align-top">
                    <p class="font-semibold"><?= e($role['label']) ?></p>
                    <p class="font-mono text-[11px] text-ink/50"><?= e($role['name']) ?></p>
                    <?php if ((int) $role['is_system'] === 1): ?>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-ink/60">système</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-ink/70"><?= e((string) ($role['description'] ?? '')) ?></td>
                <td class="px-5 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= (int) $role['user_count'] > 0 ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-ink/60' ?>">
                        <?= (int) $role['user_count'] ?>
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="inline-flex items-center gap-2">
                        <?php if (!(int) $role['is_system']): ?>
                            <a href="<?= e(Router::url('admin.roles.edit', ['publicId' => $role['public_id']])) ?>"
                               class="text-xs px-3 py-1.5 rounded-lg border border-brand/15 hover:bg-cream transition">Modifier</a>
                            <form method="POST" action="<?= e(Router::url('admin.roles.destroy', ['publicId' => $role['public_id']])) ?>"
                                  onsubmit="return confirm('Supprimer ce rôle ?');">
                                <?= CSRF::field() ?>
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 transition"
                                        <?= (int) $role['user_count'] > 0 ? 'disabled title="Rôle affecté à des utilisateurs"' : '' ?>>Supprimer</button>
                            </form>
                        <?php else: ?>
                            <span class="text-xs text-ink/40">protégé</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php $this->endSection(); ?>