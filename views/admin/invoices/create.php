<?php
use App\Support\App;
use App\Support\CSRF;
$this->layout('layouts/admin');
$this->section('title'); ?>Nouvelle facture<?php $this->endSection();
$this->section('content');
?>

<h1 class="text-2xl font-extrabold mb-6">Créer une facture</h1>

<form method="POST" action="<?= e(route('admin.invoices.store')) ?>" class="bg-white rounded-2xl border border-brand/10 p-6 max-w-3xl">
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
            <label for="project_id" class="text-sm font-medium block">Dossier lié</label>
            <select id="project_id" name="project_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <option value="">— Sans dossier —</option>
                <?php foreach ($projects as $project): ?>
                <option value="<?= (int) $project['id'] ?>" <?= (int) App::old('project_id') === (int) $project['id'] ? 'selected' : '' ?>>
                    <?= e($project['reference']) ?> — <?= e($project['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="amount" class="text-sm font-medium block">Montant *</label>
            <input id="amount" name="amount" type="number" step="0.01" min="0.01" required value="<?= e(App::old('amount')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
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
            <label for="issue_date" class="text-sm font-medium block">Date d'émission *</label>
            <input id="issue_date" name="issue_date" type="date" required value="<?= e(App::old('issue_date', date('Y-m-d'))) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div>
            <label for="due_date" class="text-sm font-medium block">Échéance</label>
            <input id="due_date" name="due_date" type="date" value="<?= e(App::old('due_date')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div class="sm:col-span-2">
            <label for="description" class="text-sm font-medium block">Prestation / description</label>
            <textarea id="description" name="description" rows="3" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15"><?= e(App::old('description')) ?></textarea>
        </div>
    </div>
    <p class="text-xs text-ink/50 mt-4">La référence (FAC-AAAA-NNNN) est générée automatiquement. La facture est créée en brouillon.</p>
    <button type="submit" class="mt-4 px-6 py-3 rounded-xl bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Créer la facture</button>
</form>

<?php $this->endSection(); ?>