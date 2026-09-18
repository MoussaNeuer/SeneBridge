<?php
use App\Support\App;
use App\Support\CSRF;
$this->layout('layouts/admin');
$this->section('title'); ?>Nouveau dossier<?php $this->endSection();
$this->section('content');
$types = ['immobilier', 'gestion_projets', 'import_export', 'auto', 'conciergerie', 'investissement'];
?>

<h1 class="text-2xl font-extrabold mb-6">Créer un dossier projet</h1>

<form method="POST" action="<?= e(route('admin.projects.store')) ?>" class="bg-white rounded-2xl border border-brand/10 p-6 max-w-3xl">
    <?= CSRF::field() ?>

    <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label for="client_id" class="text-sm font-medium block">Client *</label>
            <select id="client_id" name="client_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <option value="">— Choisir le client —</option>
                <?php foreach ($clients['items'] as $client): ?>
                <option value="<?= (int) $client['id'] ?>" <?= (int) $prefillClientId === (int) $client['id'] ? 'selected' : '' ?>>
                    <?= e(trim($client['first_name'] . ' ' . $client['last_name'])) ?> — <?= e($client['email']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="name" class="text-sm font-medium block">Nom du projet *</label>
            <input id="name" name="name" required value="<?= e(App::old('name')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
        </div>
        <div>
            <label for="type" class="text-sm font-medium block">Type *</label>
            <select id="type" name="type" required class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <?php foreach ($types as $type): ?>
                <option value="<?= e($type) ?>" <?= App::old('type', 'immobilier') === $type ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $type))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="locality" class="text-sm font-medium block">Localité</label>
            <input id="locality" name="locality" value="<?= e(App::old('locality')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
        </div>
        <div>
            <label for="budget" class="text-sm font-medium block">Budget</label>
            <input id="budget" name="budget" type="number" step="0.01" min="0" value="<?= e(App::old('budget')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
        </div>
        <div>
            <label for="currency" class="text-sm font-medium block">Devise</label>
            <select id="currency" name="currency" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <option value="XOF">XOF</option>
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
            </select>
        </div>
        <div>
            <label for="start_date" class="text-sm font-medium block">Date de début</label>
            <input id="start_date" name="start_date" type="date" value="<?= e(App::old('start_date')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div>
            <label for="expected_end_date" class="text-sm font-medium block">Échéance estimée</label>
            <input id="expected_end_date" name="expected_end_date" type="date" value="<?= e(App::old('expected_end_date')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div class="sm:col-span-2">
            <label for="counselor_id" class="text-sm font-medium block">Conseiller dédié</label>
            <select id="counselor_id" name="counselor_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <option value="">— À assigner plus tard —</option>
                <?php foreach ($counselors as $counselor): ?>
                <option value="<?= (int) $counselor['id'] ?>" <?= (int) App::old('counselor_id') === (int) $counselor['id'] ? 'selected' : '' ?>>
                    <?= e(trim($counselor['first_name'] . ' ' . $counselor['last_name'])) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="description" class="text-sm font-medium block">Description</label>
            <textarea id="description" name="description" rows="4" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15"><?= e(App::old('description')) ?></textarea>
        </div>
    </div>

    <button type="submit" class="mt-6 px-6 py-3 rounded-xl bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Créer le dossier</button>
</form>

<?php $this->endSection(); ?>