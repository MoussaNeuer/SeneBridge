<?php
use App\Support\App;
use App\Support\CSRF;
use App\Support\Router;
$this->layout('layouts/client');
$this->section('title'); ?>Demander un rendez-vous<?php $this->endSection();
$this->section('content');
?>

<a href="<?= e(Router::url('client.appointments')) ?>" class="text-sm text-brand hover:underline">← Retour à mes rendez-vous</a>

<form method="POST" action="<?= e(Router::url('client.appointments.store')) ?>" class="bg-white rounded-2xl border border-brand/10 p-6 mt-4 max-w-2xl">
    <?= CSRF::field() ?>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="project_id" class="text-sm font-medium block">Dossier concerné *</label>
            <select id="project_id" name="project_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <option value="">— Choisir un dossier —</option>
                <?php foreach ($projects as $project): ?>
                <option value="<?= (int) $project['id'] ?>" <?= (int) App::old('project_id') === (int) $project['id'] ? 'selected' : '' ?>>
                    <?= e($project['reference']) ?> — <?= e($project['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="counselor_id" class="text-sm font-medium block">Conseiller *</label>
            <select id="counselor_id" name="counselor_id" required class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <option value="">— Choisir un conseiller —</option>
                <?php foreach ($counselors as $counselor): ?>
                <option value="<?= (int) $counselor['id'] ?>" <?= (int) App::old('counselor_id') === (int) $counselor['id'] ? 'selected' : '' ?>>
                    <?= e(trim($counselor['first_name'] . ' ' . $counselor['last_name'])) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="requested_date" class="text-sm font-medium block">Date souhaitée *</label>
            <input id="requested_date" name="requested_date" type="date" required value="<?= e(App::old('requested_date')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
        </div>
        <div>
            <label for="requested_time" class="text-sm font-medium block">Heure souhaitée *</label>
            <input id="requested_time" name="requested_time" type="time" required value="<?= e(App::old('requested_time')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
        </div>
        <div class="sm:col-span-2">
            <label for="motive" class="text-sm font-medium block">Motif *</label>
            <textarea id="motive" name="motive" rows="3" required class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30"><?= e(App::old('motive')) ?></textarea>
        </div>
    </div>
    <p class="text-xs text-ink/50 mt-3">Notre équipe vous confirmera la disponibilité (choix du conseiller compris).</p>
    <button type="submit" class="mt-4 px-6 py-3 rounded-xl bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Envoyer la demande</button>
</form>

<?php $this->endSection(); ?>