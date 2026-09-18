<?php $this->layout('layouts/auth'); ?>
<?php $this->section('title'); ?>Mot de passe oublié<?php $this->endSection(); ?>
<?php $this->section('content'); ?>

<h1 class="text-2xl font-extrabold text-center">Réinitialiser votre mot de passe</h1>
<p class="text-sm opacity-70 text-center mt-2 mb-6">
    Saisissez l'adresse e-mail de votre compte. Nous vous enverrons un lien de réinitialisation.
</p>

<form method="POST" action="<?= e(app_url('forgot-password')) ?>" class="space-y-4">
    <?= \App\Support\CSRF::field() ?>

    <div>
        <label for="email" class="block text-sm font-semibold mb-1">Adresse e-mail</label>
        <input type="email" id="email" name="email" required autocomplete="email" autofocus
               value="<?= e(\App\Support\App::old('email')) ?>"
               class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
    </div>

    <button type="submit"
            class="w-full px-4 py-3 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">
        Envoyer le lien de réinitialisation
    </button>
</form>

<p class="text-sm text-center mt-6">
    <a href="<?= e(app_url('login')) ?>" class="text-brand font-semibold hover:underline">← Retour à la connexion</a>
</p>

<?php $this->endSection(); ?>