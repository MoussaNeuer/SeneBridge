<?php
use App\Support\App;
use App\Support\CSRF;
use App\Services\WorkflowService;
$this->layout('layouts/admin');
$this->section('title'); ?>Nouveau bien<?php $this->endSection();
$this->section('content');
$types = ['terrain', 'appartement', 'maison', 'villa', 'local_commercial', 'autre'];
$statuses = ['disponible', 'sous_offre', 'reserve', 'vendu', 'loue', 'archive'];
$property = $property ?? null;
?>

<h1 class="text-2xl font-extrabold mb-6"><?= $property ? 'Modifier le bien' : 'Enregistrer un bien' ?></h1>

<form method="POST" action="<?= $property ? e(route('admin.properties.update', ['publicId' => $property['public_id']])) : e(route('admin.properties.store')) ?>"
      class="bg-white rounded-2xl border border-brand/10 p-6 max-w-3xl">
    <?= CSRF::field() ?>

    <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label for="name" class="text-sm font-medium block">Nom du bien *</label>
            <input id="name" name="name" required value="<?= e(App::old('name', $property['name'] ?? '')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
        </div>
        <div>
            <label for="type" class="text-sm font-medium block">Type *</label>
            <select id="type" name="type" required class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <?php foreach ($types as $type): ?>
                <option value="<?= e($type) ?>" <?= ($property['type'] ?? '') === $type ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $type))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="status" class="text-sm font-medium block">Statut</label>
            <select id="status" name="status" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status) ?>" <?= ($property['status'] ?? 'disponible') === $status ? 'selected' : '' ?>><?= e(WorkflowService::formatLabel($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="locality" class="text-sm font-medium block">Localité</label>
            <input id="locality" name="locality" value="<?= e(App::old('locality', $property['locality'] ?? '')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div>
            <label for="price" class="text-sm font-medium block">Prix</label>
            <input id="price" name="price" type="number" step="0.01" min="0" value="<?= e(App::old('price', $property['price'] ?? '')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div>
            <label for="currency" class="text-sm font-medium block">Devise</label>
            <select id="currency" name="currency" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <?php foreach (['XOF', 'EUR', 'USD'] as $currency): ?>
                <option value="<?= e($currency) ?>" <?= ($property['currency'] ?? 'XOF') === $currency ? 'selected' : '' ?>><?= e($currency) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="project_id" class="text-sm font-medium block">Dossier associé (optionnel)</label>
            <select id="project_id" name="project_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <option value="">— Aucun —</option>
                <?php foreach ($projects as $projectRow): ?>
                <option value="<?= (int) $projectRow['id'] ?>" <?= (int) ($property['project_id'] ?? 0) === (int) $projectRow['id'] ? 'selected' : '' ?>>
                    <?= e($projectRow['reference']) ?> — <?= e($projectRow['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="owner_client_id" class="text-sm font-medium block">Propriétaire (client, optionnel)</label>
            <select id="owner_client_id" name="owner_client_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <option value="">— Aucun —</option>
                <?php foreach ($clients['items'] as $client): ?>
                <option value="<?= (int) $client['id'] ?>" <?= (int) ($property['owner_client_id'] ?? 0) === (int) $client['id'] ? 'selected' : '' ?>>
                    <?= e(trim($client['first_name'] . ' ' . $client['last_name'])) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="description" class="text-sm font-medium block">Description</label>
            <textarea id="description" name="description" rows="4" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15"><?= e(App::old('description', $property['description'] ?? '')) ?></textarea>
        </div>
    </div>

    <button type="submit" class="mt-6 px-6 py-3 rounded-xl bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">
        <?= $property ? 'Enregistrer les modifications' : 'Enregistrer le bien' ?>
    </button>
</form>

<?php $this->endSection(); ?>