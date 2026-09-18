<?php
use App\Support\App;
use App\Support\CSRF;
$this->layout('layouts/app');
$this->section('title'); ?>Démarrer un projet — SeneBridge<?php $this->endSection();
$this->section('content');
$types = [
    'immobilier' => 'Immobilier',
    'gestion_projets' => 'Gestion de projets',
    'import_export' => 'Import / Export',
    'auto' => 'SeneBridge Auto',
    'conciergerie' => 'Conciergerie',
    'investissement' => 'Investissement',
];
$intents = [
    'je_cherche_un_bien' => 'Je cherche un bien',
    'jai_un_projet' => 'J\'ai un projet à réaliser',
    'accompagnement' => 'J\'ai besoin d\'un accompagnement',
];
?>

<section class="bg-brand text-brand-foreground">
    <div class="max-w-7xl mx-auto px-4 py-14 text-center">
        <p class="text-sm tracking-widest uppercase opacity-70">C'est parti</p>
        <h1 class="text-3xl md:text-5xl font-extrabold mt-2">Démarrer un projet</h1>
        <p class="mt-3 text-brand-foreground/80 max-w-2xl mx-auto">
            Décrivez votre besoin : un conseiller SeneBridge vous recontacte rapidement (moins de 24h).
        </p>
    </div>
</section>

<div class="max-w-3xl mx-auto px-4 py-12">
    <form method="POST" action="<?= e(route('pages.start-project.submit')) ?>" class="bg-white rounded-2xl border border-brand/10 p-6 md:p-8">
        <?= CSRF::field() ?>

        <div class="mb-6">
            <label class="text-sm font-medium block mb-2">Quel est votre besoin ? *</label>
            <div class="grid gap-3 sm:grid-cols-3">
                <?php foreach ($intents as $value => $label): ?>
                <label class="flex items-center gap-2 px-4 py-3 rounded-xl border border-brand/15 cursor-pointer hover:border-brand/40 transition text-sm">
                    <input type="radio" name="intent" value="<?= e($value) ?>"
                           <?= App::old('intent', 'jai_un_projet') === $value ? 'checked' : '' ?> class="accent-brand">
                    <?= e($label) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-6">
            <label for="project_type" class="text-sm font-medium block mb-2">Type de projet *</label>
            <select id="project_type" name="project_type" required
                    class="w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
                <?php foreach ($types as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= App::old('project_type') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="first_name" class="text-sm font-medium">Prénom *</label>
                <input id="first_name" name="first_name" required value="<?= e(App::old('first_name')) ?>"
                       class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
            </div>
            <div>
                <label for="last_name" class="text-sm font-medium">Nom *</label>
                <input id="last_name" name="last_name" required value="<?= e(App::old('last_name')) ?>"
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
                <label for="locality" class="text-sm font-medium">Localité</label>
                <input id="locality" name="locality" placeholder="Ex. Dakar, Thiès…" value="<?= e(App::old('locality')) ?>"
                       class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
            </div>
            <div>
                <label for="budget" class="text-sm font-medium">Budget (XOF)</label>
                <input id="budget" name="budget" type="number" min="0" placeholder="Ex. 25000000" value="<?= e(App::old('budget')) ?>"
                       class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
            </div>
        </div>

        <div class="mt-4">
            <label for="description" class="text-sm font-medium">Décrivez votre projet</label>
            <textarea id="description" name="description" rows="5" placeholder="Localisation, taille, délais souhaités, particularités…"
                      class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30"><?= e(App::old('description')) ?></textarea>
        </div>

        <button type="submit" class="mt-6 w-full px-6 py-3 rounded-xl bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">
            Envoyer ma demande
        </button>
        <p class="text-xs text-ink/50 mt-3 text-center">
            Vos données restent confidentielles. Vous serez contacté uniquement au sujet de votre demande.
        </p>
    </form>
</div>

<?php $this->endSection(); ?>