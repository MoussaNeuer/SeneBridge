<?php
use App\Support\CSRF;
use App\Support\Router;
use App\Services\WorkflowService;
$this->layout('layouts/admin');
$this->section('title'); ?>Demande de <?= e($request['first_name'] . ' ' . $request['last_name']) ?><?php $this->endSection();
$this->section('content');
$intents = ['je_cherche_un_bien' => 'Je cherche un bien', 'jai_un_projet' => 'J\'ai un projet', 'accompagnement' => 'Accompagnement'];
?>

<a href="<?= e(Router::url('admin.requests')) ?>" class="text-sm text-brand hover:underline">← Retour aux demandes</a>

<div class="mt-4 grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-brand/10 p-6">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-extrabold"><?= e(trim($request['first_name'] . ' ' . $request['last_name'])) ?></h1>
            <span class="px-3 py-1 rounded-full text-sm font-semibold
                <?= $request['status'] === 'nouveau' ? 'bg-gold/15 text-brand' : ($request['status'] === 'qualification' ? 'bg-emerald-50 text-emerald-brand' : ($request['status'] === 'converti' ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-ink/60')) ?>">
                <?= e(WorkflowService::formatLabel($request['status'])) ?>
            </span>
        </div>
        <dl class="mt-5 grid sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-xs text-ink/50">E-mail</dt><dd class="font-medium"><?= e($request['email']) ?></dd></div>
            <div><dt class="text-xs text-ink/50">Téléphone</dt><dd class="font-medium"><?= e($request['phone'] ?: '—') ?></dd></div>
            <div><dt class="text-xs text-ink/50">Besoin</dt><dd class="font-medium"><?= e($intents[$request['intent']] ?? $request['intent']) ?></dd></div>
            <div><dt class="text-xs text-ink/50">Type de projet</dt><dd class="font-medium"><?= e(ucfirst(str_replace('_', ' ', $request['project_type']))) ?></dd></div>
            <div><dt class="text-xs text-ink/50">Localité</dt><dd class="font-medium"><?= e($request['locality'] ?: '—') ?></dd></div>
            <div><dt class="text-xs text-ink/50">Budget</dt><dd class="font-medium"><?= $request['budget'] !== null ? format_number($request['budget']) . ' ' . e($request['currency']) : '—' ?></dd></div>
            <div><dt class="text-xs text-ink/50">Reçue le</dt><dd class="font-medium"><?= e(date('d/m/Y H:i', strtotime($request['created_at']))) ?></dd></div>
            <div><dt class="text-xs text-ink/50">Traité par</dt><dd class="font-medium"><?= $request['handled_by'] ? '#' . (int) $request['handled_by'] : '—' ?></dd></div>
        </dl>
        <?php if ($request['description']): ?>
            <div class="mt-5">
                <h2 class="text-sm font-semibold text-ink/60 uppercase">Description du projet</h2>
                <p class="mt-2 text-sm text-ink/75 leading-relaxed bg-cream rounded-xl p-4"><?= nl2br(e($request['description'])) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl border border-brand/10 p-6 h-fit">
        <h2 class="font-bold mb-4">Changer le statut</h2>
        <form method="POST" action="<?= e(route('admin.requests.status', ['publicId' => $request['public_id']])) ?>">
            <?= CSRF::field() ?>
            <select name="status" class="w-full px-3 py-2 rounded-lg border border-brand/15">
                <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status) ?>" <?= $request['status'] === $status ? 'selected' : '' ?>><?= e(WorkflowService::formatLabel($status)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="mt-4 w-full px-5 py-2.5 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Enregistrer</button>
        </form>

        <?php if ($request['status'] === 'converti' || $request['user_id']): ?>
        <div class="mt-5 pt-4 border-t border-brand/10">
            <p class="text-xs text-ink/50">Astuce : créez un dossier pour ce demandeur via le module Projets (recherchez-le par e-mail dans le module Clients).</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection(); ?>