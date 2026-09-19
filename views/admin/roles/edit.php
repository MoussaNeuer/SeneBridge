<?php
use App\Support\App;
use App\Support\CSRF;
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Modifier le rôle : <?= e($role['label']) ?><?php $this->endSection();
$this->section('content');
?>

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <a href="<?= e(Router::url('admin.roles')) ?>" class="text-sm text-brand hover:underline">← Retour aux rôles</a>
        <h1 class="text-2xl font-extrabold mt-1">Modifier « <?= e($role['label']) ?> »</h1>
        <p class="text-sm text-ink/60 mt-1"><span class="font-mono text-[12px]"><?= e($role['name']) ?></span> · <?= (int) $role['user_count'] ?> utilisateur<?= (int) $role['user_count'] > 1 ? 's' : '' ?> affecté<?= (int) $role['user_count'] > 1 ? 's' : '' ?></p>
    </div>
</div>

<form method="POST" action="<?= e(Router::url('admin.roles.update', ['publicId' => $role['public_id']])) ?>">
    <?= CSRF::field() ?>
    <div class="bg-white rounded-2xl border border-brand/10 p-6 mb-6">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="r_label" class="text-sm font-medium block">Libellé *</label>
                <input id="r_label" name="label" required maxlength="120" value="<?= e(App::old('label', (string) $role['label'])) ?>"
                       class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
            </div>
            <div>
                <label class="text-sm font-medium block">Nom technique <span class="text-ink/40">(immuable)</span></label>
                <input type="text" value="<?= e($role['name']) ?>" disabled class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 bg-cream/70">
            </div>
        </div>
        <div class="mt-4">
            <label for="r_description" class="text-sm font-medium block">Description</label>
            <textarea id="r_description" name="description" rows="2" maxlength="255"
                      class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15"><?= e(App::old('description', (string) ($role['description'] ?? ''))) ?></textarea>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-brand/10 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold">Permissions accordées</h2>
            <button type="button" id="toggleAll" class="text-xs px-3 py-1.5 rounded-lg border border-brand/15 hover:bg-cream transition">Tout cocher</button>
        </div>
        <?php $this->include('admin.roles.partials.permissions', ['groups' => $groups, 'selected' => $rolePermissionIds, 'fieldName' => 'permissions[]']); ?>
    </div>

    <button type="submit" class="px-6 py-2.5 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Enregistrer</button>
</form>

<script src="<?= e(asset_url('assets/js/roles.js')) ?>" defer></script>

<?php $this->endSection(); ?>