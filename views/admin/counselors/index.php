<?php
use App\Support\App;
use App\Support\CSRF;
use App\Support\Gate;
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Conseillers<?php $this->endSection();
$this->section('content');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Conseillers</h1>
        <p class="text-sm text-ink/60 mt-1"><?= (int) $pagination['total'] ?> conseiller<?= (int) $pagination['total'] > 1 ? 's' : '' ?> dans l'équipe.</p>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-brand/10 overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
                <tr>
                    <th class="px-5 py-3">Nom</th>
                    <th class="px-5 py-3">E-mail</th>
                    <th class="px-5 py-3 hidden md:table-cell">Statut</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagination['items'] as $counselor): ?>
                <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                    <td class="px-5 py-3 font-medium"><?= e(trim($counselor['first_name'] . ' ' . $counselor['last_name'])) ?></td>
                    <td class="px-5 py-3"><?= e($counselor['email']) ?></td>
                    <td class="px-5 py-3 hidden md:table-cell">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $counselor['status'] === 'active' ? 'bg-emerald-50 text-emerald-brand' : 'bg-red-50 text-red-700' ?>">
                            <?= $counselor['status'] === 'active' ? 'Actif' : 'Suspendu' ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="inline-flex items-center gap-2">
                            <?php if (Gate::allows('roles.manage') && (int) $counselor['id'] !== (int) $user['id']): ?>
                            <form method="POST" action="<?= e(Router::url('admin.users.role', ['publicId' => $counselor['public_id']])) ?>" class="flex items-center gap-2">
                                <?= CSRF::field() ?>
                                <select name="role_id" class="text-xs px-2 py-1.5 rounded-lg border border-brand/15 bg-white">
                                    <?php foreach ($roles as $role): ?>
                                    <option value="<?= (int) $role['id'] ?>" <?= (int) $role['id'] === (int) $counselor['role_id'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-brand/15 hover:bg-cream transition">Changer</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" action="<?= e(Router::url('admin.counselors.status', ['publicId' => $counselor['public_id']])) ?>">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="status" value="<?= $counselor['status'] === 'active' ? 'suspended' : 'active' ?>">
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-brand/15 hover:bg-cream transition">
                                    <?= $counselor['status'] === 'active' ? 'Suspendre' : 'Réactiver' ?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($pagination['items'] === []): ?>
            <p class="text-sm text-ink/60 text-center py-8">Aucun conseiller pour le moment.</p>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl border border-brand/10 p-6 h-fit">
        <h2 class="font-bold text-lg mb-4">Ajouter un conseiller</h2>
        <form method="POST" action="<?= e(route('admin.counselors.store')) ?>">
            <?= CSRF::field() ?>
            <div class="space-y-4">
                <div>
                    <label for="c_first_name" class="text-sm font-medium block">Prénom *</label>
                    <input id="c_first_name" name="first_name" required value="<?= e(App::old('first_name')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                </div>
                <div>
                    <label for="c_last_name" class="text-sm font-medium block">Nom *</label>
                    <input id="c_last_name" name="last_name" required value="<?= e(App::old('last_name')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                </div>
                <div>
                    <label for="c_email" class="text-sm font-medium block">E-mail *</label>
                    <input id="c_email" name="email" type="email" required value="<?= e(App::old('email')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                </div>
                <div>
                    <label for="c_phone" class="text-sm font-medium block">Téléphone</label>
                    <input id="c_phone" name="phone" value="<?= e(App::old('phone')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                </div>
                <div>
                    <label for="c_password" class="text-sm font-medium block">Mot de passe (min. 12) *</label>
                    <input id="c_password" name="password" type="password" required minlength="12" autocomplete="new-password"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                </div>
            </div>
            <button type="submit" class="mt-5 w-full px-5 py-2.5 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Créer le conseiller</button>
        </form>
    </div>
</div>

<?php $this->endSection(); ?>