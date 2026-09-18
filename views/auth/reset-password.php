<?php $this->layout('layouts/auth'); ?>
<?php $this->section('title'); ?>Nouveau mot de passe<?php $this->endSection(); ?>
<?php $this->section('content'); ?>

<h1 class="text-2xl font-extrabold text-center">Choisir un nouveau mot de passe</h1>
<p class="text-sm opacity-70 text-center mt-2 mb-6">Pour le compte lié à ce lien de réinitialisation.</p>

<form method="POST" action="<?= e(app_url('reset-password')) ?>" class="space-y-4">
    <?= \App\Support\CSRF::field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div>
        <label for="email" class="block text-sm font-semibold mb-1">Adresse e-mail du compte</label>
        <input type="email" id="email" name="email" required autocomplete="email"
               value="<?= e(\App\Support\App::old('email')) ?>"
               class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
    </div>

    <div>
        <label for="password" class="block text-sm font-semibold mb-1">Nouveau mot de passe</label>
        <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8"
               class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-semibold mb-1">Confirmation</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" minlength="8"
               class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
    </div>

    <button type="submit"
            class="w-full px-4 py-3 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">
        Réinitialiser le mot de passe
    </button>
</form>

<?php $this->endSection(); ?>