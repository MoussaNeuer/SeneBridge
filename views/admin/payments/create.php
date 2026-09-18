<?php
use App\Support\App;
use App\Support\CSRF;
$this->layout('layouts/admin');
$this->section('title'); ?>Nouveau paiement<?php $this->endSection();
$this->section('content');
?>

<h1 class="text-2xl font-extrabold mb-6">Enregistrer un paiement</h1>

<form method="POST" action="<?= e(route('admin.payments.store')) ?>" enctype="multipart/form-data" class="bg-white rounded-2xl border border-brand/10 p-6 max-w-3xl">
    <?= CSRF::field() ?>
    <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label for="invoice_id" class="text-sm font-medium block">Facture concernée</label>
            <select id="invoice_id" name="invoice_id" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15" onchange="this.form.querySelector('#client_id').value = this.selectedOptions[0]?.dataset.clientId || ''; this.form.querySelector('#amount').value = this.selectedOptions[0]?.dataset.amount || '';">
                <option value="" data-client-id="" data-amount="">— Paiement sans facture —</option>
                <?php foreach ($invoices as $invoice): ?>
                <option value="<?= (int) $invoice['id'] ?>" data-client-id="<?= (int) $invoice['client_id'] ?>" data-amount="<?= e($invoice['amount']) ?>"
                    <?= (int) $prefill['invoice_id'] === (int) $invoice['id'] ? 'selected' : '' ?>>
                    <?= e($invoice['number']) ?> — <?= format_number($invoice['amount']) ?> <?= e($invoice['currency']) ?> (<?= e(trim($invoice['first_name'] . ' ' . $invoice['last_name'])) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="client_id" class="text-sm font-medium block">Client *</label>
            <select id="client_id" name="client_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <option value="">— Choisir le client —</option>
                <?php foreach ($clients['items'] as $client): ?>
                <option value="<?= (int) $client['id'] ?>" <?= (int) $prefill['client_id'] === (int) $client['id'] ? 'selected' : '' ?>>
                    <?= e(trim($client['first_name'] . ' ' . $client['last_name'])) ?> — <?= e($client['email']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="amount" class="text-sm font-medium block">Montant *</label>
            <input id="amount" name="amount" type="number" step="0.01" min="0.01" required value="<?= e(App::old('amount') !== '' ? App::old('amount') : $prefill['amount']) ?>"
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
            <label for="payment_date" class="text-sm font-medium block">Date de règlement *</label>
            <input id="payment_date" name="payment_date" type="date" required value="<?= e(App::old('payment_date', date('Y-m-d'))) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div>
            <label for="method" class="text-sm font-medium block">Méthode *</label>
            <select id="method" name="method" required class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <?php foreach (\App\Models\Payment::METHOD_LABELS as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= App::old('method') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="reference" class="text-sm font-medium block">Référence (transféré / virement)</label>
            <input id="reference" name="reference" value="<?= e(App::old('reference')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
        </div>
        <div class="sm:col-span-2">
            <label for="receipt" class="text-sm font-medium block">Reçu / preuve (PDF, image)</label>
            <input id="receipt" name="receipt" type="file" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div class="sm:col-span-2">
            <label for="notes" class="text-sm font-medium block">Notes</label>
            <textarea id="notes" name="notes" rows="2" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15"><?= e(App::old('notes')) ?></textarea>
        </div>
    </div>
    <p class="text-xs text-ink/50 mt-4">Le paiement est enregistré « en cours » puis validé par l'équipe (cela met à jour la facture).</p>
    <button type="submit" class="mt-4 px-6 py-3 rounded-xl bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Enregistrer le paiement</button>
</form>

<?php $this->endSection(); ?>