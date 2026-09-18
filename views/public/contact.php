<?php
use App\Support\CSRF;
use App\Support\App;
$this->layout('layouts/app');
$this->section('title'); ?>Contact — SeneBridge<?php $this->endSection();
$this->section('content');
?>

<section class="bg-brand text-brand-foreground">
    <div class="max-w-7xl mx-auto px-4 py-14 text-center">
        <p class="text-sm tracking-widest uppercase opacity-70">Parlons-en</p>
        <h1 class="text-3xl md:text-5xl font-extrabold mt-2">Contactez-nous</h1>
        <p class="mt-3 text-brand-foreground/80 max-w-2xl mx-auto">Une question, un projet, un partenariat ? Notre équipe vous répond en moins de 24h.</p>
    </div>
</section>

<div class="max-w-6xl mx-auto px-4 py-12 grid gap-8 md:grid-cols-5">
    <div class="md:col-span-2 space-y-4">
        <div class="bg-white rounded-2xl border border-brand/10 p-5">
            <p class="font-bold">📍 Adresse</p>
            <p class="text-sm text-ink/70 mt-1">Dakar, Sénégal</p>
        </div>
        <div class="bg-white rounded-2xl border border-brand/10 p-5">
            <p class="font-bold">✉ E-mail</p>
            <p class="text-sm text-ink/70 mt-1"><?= e($contactEmail) ?></p>
        </div>
        <div class="bg-white rounded-2xl border border-brand/10 p-5">
            <p class="font-bold">☎ Téléphone</p>
            <p class="text-sm text-ink/70 mt-1"><?= e($contactPhone) ?></p>
        </div>
        <div class="bg-white rounded-2xl border border-brand/10 p-5">
            <p class="font-bold">🕘 Horaires</p>
            <p class="text-sm text-ink/70 mt-1">Lun – Ven : 8h–18h (GMT)<br>Sam : 9h–13h</p>
        </div>
    </div>

    <div class="md:col-span-3">
        <form method="POST" action="<?= e(route('pages.contact.submit')) ?>" class="bg-white rounded-2xl border border-brand/10 p-6 md:p-8">
            <?= CSRF::field() ?>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="text-sm font-medium">Nom complet *</label>
                    <input id="name" name="name" required value="<?= e(App::old('name')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                </div>
                <div>
                    <label for="email" class="text-sm font-medium">E-mail *</label>
                    <input id="email" name="email" type="email" required value="<?= e(App::old('email')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                </div>
                <div>
                    <label for="phone" class="text-sm font-medium">Téléphone</label>
                    <input id="phone" name="phone" value="<?= e(App::old('phone')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                </div>
                <div>
                    <label for="subject" class="text-sm font-medium">Sujet *</label>
                    <input id="subject" name="subject" required value="<?= e(App::old('subject')) ?>"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                </div>
            </div>
            <div class="mt-4">
                <label for="message" class="text-sm font-medium">Message *</label>
                <textarea id="message" name="message" rows="6" required
                          class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30"><?= e(App::old('message')) ?></textarea>
            </div>
            <button type="submit" class="mt-5 px-6 py-3 rounded-xl bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">
                Envoyer le message
            </button>
        </form>
    </div>
</div>

<?php $this->endSection(); ?>