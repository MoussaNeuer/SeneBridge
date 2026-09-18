<?php $this->layout('layouts/auth'); ?>
<?php $this->section('title'); ?>Créer un compte<?php $this->endSection(); ?>
<?php $this->section('content'); ?>

<h1 class="text-2xl font-extrabold text-center">Créer votre compte</h1>
<p class="text-sm opacity-70 text-center mt-2 mb-6">Rejoignez SeneBridge et suivez vos projets en toute transparence.</p>

<form method="POST" action="<?= e(app_url('register')) ?>" class="space-y-4">
    <?= \App\Support\CSRF::field() ?>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="first_name" class="block text-sm font-semibold mb-1">Prénom</label>
            <input type="text" id="first_name" name="first_name" required autocomplete="given-name" autofocus
                   value="<?= e(\App\Support\App::old('first_name')) ?>"
                   class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
        </div>
        <div>
            <label for="last_name" class="block text-sm font-semibold mb-1">Nom</label>
            <input type="text" id="last_name" name="last_name" required autocomplete="family-name"
                   value="<?= e(\App\Support\App::old('last_name')) ?>"
                   class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
        </div>
    </div>

    <div>
        <label for="email" class="block text-sm font-semibold mb-1">Adresse e-mail</label>
        <input type="email" id="email" name="email" required autocomplete="email"
               value="<?= e(\App\Support\App::old('email')) ?>"
               class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
    </div>

    <div>
        <label for="phone" class="block text-sm font-semibold mb-1">Téléphone <span class="opacity-50">(optionnel)</span></label>
        <input type="tel" id="phone" name="phone" autocomplete="tel"
               value="<?= e(\App\Support\App::old('phone')) ?>"
               placeholder="+221 77 000 00 00"
               class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="password" class="block text-sm font-semibold mb-1">Mot de passe</label>
            <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8"
                   class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-semibold mb-1">Confirmation</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" minlength="8"
                   class="w-full px-4 py-2.5 rounded-lg border border-brand/20 bg-white focus:outline-none focus:ring-2 focus:ring-brand">
        </div>
    </div>
    <p class="text-xs opacity-60 -mt-2">8 caractères minimum, avec au moins une lettre et un chiffre.</p>

    <button type="submit"
            class="w-full px-4 py-3 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">
        Créer mon compte
    </button>
</form>

<p class="text-sm text-center mt-6 opacity-75">
    Déjà inscrit ?
    <a href="<?= e(app_url('login')) ?>" class="text-brand font-semibold hover:underline">Connectez-vous</a>
</p>

<?php $this->endSection(); ?>