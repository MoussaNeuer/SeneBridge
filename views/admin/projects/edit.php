<?php
use App\Support\App;
use App\Support\CSRF;
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Modifier <?= e($project['reference']) ?><?php $this->endSection();
$this->section('content');
$types = ['immobilier', 'gestion_projets', 'import_export', 'auto', 'conciergerie', 'investissement'];
$statuses = ['en_cours', 'bloque', 'termine', 'archive'];
?>

<a href="<?= e(Router::url('admin.projects.show', ['publicId' => $project['public_id']])) ?>" class="text-sm text-brand hover:underline">← Retour au dossier</a>
<h1 class="text-2xl font-extrabold mt-4 mb-6">Modifier le dossier <?= e($project['reference']) ?></h1>

<form method="POST" action="<?= e(route('admin.projects.update', ['publicId' => $project['public_id']])) ?>" class="bg-white rounded-2xl border border-brand/10 p-6 max-w-3xl">
    <?= CSRF::field() ?>

    <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label for="name" class="text-sm font-medium block">Nom du projet *</label>
            <input id="name" name="name" required value="<?= e(App::old('name', $project['name'])) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
        </div>
        <div>
            <label for="type" class="text-sm font-medium block">Type</label>
            <select id="type" name="type" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <?php foreach ($types as $type): ?>
                <option value="<?= e($type) ?>" <?= $project['type'] === $type ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $type))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="status" class="text-sm font-medium block">Statut</label>
            <select id="status" name="status" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status) ?>" <?= $project['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $status))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="locality" class="text-sm font-medium block">Localité</label>
            <input id="locality" name="locality" value="<?= e(App::old('locality', $project['locality'])) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div>
            <label for="budget" class="text-sm font-medium block">Budget</label>
            <input id="budget" name="budget" type="number" step="0.01" min="0"
                   value="<?= e(App::old('budget', $project['budget'])) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div>
            <label for="currency" class="text-sm font-medium block">Devise</label>
            <select id="currency" name="currency" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <?php foreach (['XOF', 'EUR', 'USD'] as $currency): ?>
                <option value="<?= e($currency) ?>" <?= $project['currency'] === $currency ? 'selected' : '' ?>><?= e($currency) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="start_date" class="text-sm font-medium block">Date de début</label>
            <input id="start_date" name="start_date" type="date" value="<?= e(App::old('start_date', $project['start_date'])) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div>
            <label for="expected_end_date" class="text-sm font-medium block">Échéance estimée</label>
            <input id="expected_end_date" name="expected_end_date" type="date" value="<?= e(App::old('expected_end_date', $project['expected_end_date'])) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div class="sm:col-span-2">
            <label for="description" class="text-sm font-medium block">Description</label>
            <textarea id="description" name="description" rows="4" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15"><?= e(App::old('description', $project['description'])) ?></textarea>
        </div>
    </div>

    <button type="submit" class="mt-6 px-6 py-3 rounded-xl bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Enregistrer</button>
</form>

<?php $this->endSection(); ?>