<?php
$this->layout('layouts/admin');
$this->section('title'); ?>Système<?php $this->endSection();
$this->section('content');
$icons = [
    'application' => 'Dashboard',
    'runtime' => 'Serveur',
    'base_de_donnees' => 'Base de données',
    'extensions' => 'Extensions PHP',
    'securite' => 'Sécurité',
    'espace' => 'Espace & sauvegardes',
];
$descriptions = [
    'application' => 'Identité de l\'application et réglages généraux.',
    'runtime' => 'Interpréteur PHP et limites du serveur.',
    'base_de_donnees' => 'Connexion MySQL active.',
    'extensions' => 'Modules requis pour le fonctionnement.',
    'securite' => 'Paramètres de session et de transport.',
    'espace' => 'Sauvegardes disponibles et stockage.',
];
?>

<div class="mb-6">
    <h1 class="text-2xl font-extrabold">Informations système</h1>
    <p class="text-sm text-ink/60 mt-1">État de la plateforme, du serveur et de la base de données.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($sections as $key => $rows): ?>
    <section class="bg-white rounded-2xl border border-brand/10 p-5">
        <div class="flex items-center gap-2.5 mb-1">
            <span class="w-2 h-2 rounded-full bg-emerald-brand"></span>
            <h2 class="text-sm font-extrabold"><?= e($icons[$key] ?? ucfirst($key)) ?></h2>
        </div>
        <p class="text-xs text-ink/50 mb-4"><?= e($descriptions[$key] ?? '') ?></p>

        <dl class="divide-y divide-brand/5">
            <?php foreach ($rows as [$label, $value]): $ok = $value === 'OK'; $missing = $value === 'Manquante'; ?>
            <div class="flex items-center justify-between gap-4 py-2.5">
                <dt class="text-sm text-ink/60"><?= e($label) ?></dt>
                <dd class="text-sm font-semibold text-right break-all">
                    <?php if ($ok): ?>
                        <span class="text-emerald-brand">✔ OK</span>
                    <?php elseif ($missing): ?>
                        <span class="text-red-600">✘ Manquante</span>
                    <?php else: ?>
                        <?= e((string) $value) ?>
                    <?php endif; ?>
                </dd>
            </div>
            <?php endforeach; ?>
        </dl>
    </section>
    <?php endforeach; ?>
</div>
<?php $this->endSection(); ?>