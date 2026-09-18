<?php
use App\Support\App;
use App\Support\CSRF;
use App\Support\Gate;
$this->layout('layouts/client');
$this->section('title'); ?>Mon profil<?php $this->endSection();
$this->section('content');
?>

<h1 class="text-2xl font-extrabold mb-6">Mon profil</h1>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="bg-white rounded-2xl border border-brand/10 p-6">
        <h2 class="font-bold text-lg mb-4">Informations personnelles</h2>
        <form method="POST" action="<?= e(route('client.profile.update')) ?>">
            <?= CSRF::field() ?>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="text-sm font-medium">Prénom</label>
                    <input id="first_name" name="first_name" required value="<?= e(App::old('first_name', $user['first_name'] ?? '')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                </div>
                <div>
                    <label for="last_name" class="text-sm font-medium">Nom</label>
                    <input id="last_name" name="last_name" required value="<?= e(App::old('last_name', $user['last_name'] ?? '')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                </div>
                <div>
                    <label for="phone" class="text-sm font-medium">Téléphone</label>
                    <input id="phone" name="phone" value="<?= e(App::old('phone', $user['phone'] ?? '')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                </div>
                <div>
                    <label for="locality" class="text-sm font-medium">Localité</label>
                    <input id="locality" name="locality" value="<?= e(App::old('locality', $user['locality'] ?? '')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                </div>
            </div>
            <div class="mt-4">
                <label for="language" class="text-sm font-medium">Langue</label>
                <select id="language" name="language" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                    <option value="fr" <?= ($user['language'] ?? 'fr') === 'fr' ? 'selected' : '' ?>>Français</option>
                    <option value="en" <?= ($user['language'] ?? 'fr') === 'en' ? 'selected' : '' ?>>English</option>
                </select>
            </div>
            <button type="submit" class="mt-5 px-5 py-2.5 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">
                Enregistrer
            </button>
        </form>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-brand/10 p-6">
            <h2 class="font-bold text-lg mb-1">Adresse e-mail</h2>
            <p class="text-sm text-ink/70"><?= e($user['email'] ?? '') ?></p>
            <p class="text-xs mt-1 <?= empty($user['email_verified_at']) ? 'text-amber-600' : 'text-emerald-brand' ?>">
                <?= empty($user['email_verified_at']) ? '⚑ Non vérifiée' : '✔ Adresse vérifiée' ?>
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-brand/10 p-6">
            <h2 class="font-bold text-lg mb-4">Changer mon mot de passe</h2>
            <form method="POST" action="<?= e(route('client.profile.password')) ?>">
                <?= CSRF::field() ?>
                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="text-sm font-medium">Mot de passe actuel</label>
                        <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                               class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                    </div>
                    <div>
                        <label for="password" class="text-sm font-medium">Nouveau mot de passe</label>
                        <input id="password" name="password" type="password" required minlength="12" autocomplete="new-password"
                               class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                    </div>
                    <div>
                        <label for="password_confirmation" class="text-sm font-medium">Confirmer le mot de passe</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                               class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                    </div>
                </div>
                <button type="submit" class="mt-5 px-5 py-2.5 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">
                    Modifier le mot de passe
                </button>
            </form>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>