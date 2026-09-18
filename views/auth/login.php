<?php $this->layout('layouts/auth'); ?>
<?php $this->section('title'); ?>Connexion<?php $this->endSection(); ?>
<?php $this->section('content'); ?>

<h1 class="text-2xl font-extrabold text-center">Connexion à votre espace</h1>
<p class="text-sm opacity-70 text-center mt-2 mb-6">Accédez à vos projets, documents et paiements.</p>

<form method="POST" action="<?= e(app_url('login')) ?>" class="space-y-4">
    <?= \App\Support\CSRF::field() ?>

    <div>
        <label for="email" class="block text-sm font-semibold mb-1">Adresse e-mail</label>
        <input type="email" id="email" name="email" required autocomplete="email" autofocus
               value="<?= e(\App\Support\App::old('email')) ?>"
               class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
    </div>

    <div>
        <div class="flex items-center justify-between mb-1">
            <label for="password" class="block text-sm font-semibold">Mot de passe</label>
            <a href="<?= e(app_url('forgot-password')) ?>" class="text-xs text-brand hover:underline">Mot de passe oublié ?</a>
        </div>
        <input type="password" id="password" name="password" required autocomplete="current-password"
               class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
    </div>

    <button type="submit"
            class="w-full px-4 py-3 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">
        Se connecter
    </button>
</form>

<p class="text-sm text-center mt-6 opacity-75">
    Pas encore de compte ?
    <a href="<?= e(app_url('register')) ?>" class="text-brand font-semibold hover:underline">Inscrivez-vous</a>
</p>

<?php $this->endSection(); ?>