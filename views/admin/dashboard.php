<?php
use App\Support\Router;
use App\Services\WorkflowService;
$this->layout('layouts/admin');
$this->section('title'); ?>Tableau de bord<?php $this->endSection();
$this->section('content');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Tableau de bord</h1>
        <p class="text-sm text-ink/60 mt-1">Vue d'ensemble de l'activité SeneBridge.</p>
    </div>
    <div class="flex gap-3">
        <a href="<?= e(Router::url('admin.projects.create')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Nouveau dossier</a>
        <a href="<?= e(Router::url('admin.counselors')) ?>" class="px-4 py-2 rounded-lg border border-brand/15 text-sm hover:bg-cream transition">+ Conseiller</a>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Dossiers en cours</p>
        <p class="text-3xl font-extrabold text-brand mt-1"><?= (int) ($stats['en_cours'] ?? 0) ?></p>
        <p class="text-xs text-ink/45 mt-1"><?= (int) ($stats['bloque'] ?? 0) ?> bloqués · <?= (int) ($stats['termine'] ?? 0) ?> terminés</p>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Clients</p>
        <p class="text-3xl font-extrabold text-brand mt-1"><?= (int) $clientCount ?></p>
        <a href="<?= e(Router::url('admin.clients')) ?>" class="text-xs text-brand hover:underline">Gérer →</a>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Nouvelles demandes</p>
        <p class="text-3xl font-extrabold <?= $newRequests > 0 ? 'text-gold' : 'text-brand' ?> mt-1"><?= (int) $newRequests ?></p>
        <a href="<?= e(Router::url('admin.requests')) ?>" class="text-xs text-brand hover:underline">Voir les demandes →</a>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Messages non lus</p>
        <p class="text-3xl font-extrabold text-brand mt-1"><?= (int) $unreadContacts ?></p>
        <a href="<?= e(Router::url('admin.contacts')) ?>" class="text-xs text-brand hover:underline">Boîte de réception →</a>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Factures à recouvrer</p>
        <p class="text-3xl font-extrabold <?= ($invoiceStats['a_recouvrer'] ?? 0) > 0 ? 'text-gold' : 'text-brand' ?> mt-1">
            <?= (int) ($invoiceStats['a_recouvrer'] ?? 0) ?></p>
        <a href="<?= e(Router::url('admin.invoices')) ?>" class="text-xs text-brand hover:underline">Voir les factures →</a>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Paiements à valider</p>
        <p class="text-3xl font-extrabold <?= ($paymentStats['en_cours'] ?? 0) > 0 ? 'text-gold' : 'text-brand' ?> mt-1">
            <?= (int) ($paymentStats['en_cours'] ?? 0) ?></p>
        <a href="<?= e(Router::url('admin.payments')) ?>" class="text-xs text-brand hover:underline">Valider les paiements →</a>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Rendez-vous à confirmer</p>
        <p class="text-3xl font-extrabold <?= ($appointmentStats['demande'] ?? 0) > 0 ? 'text-gold' : 'text-brand' ?> mt-1">
            <?= (int) ($appointmentStats['demande'] ?? 0) ?></p>
        <a href="<?= e(Router::url('admin.appointments')) ?>" class="text-xs text-brand hover:underline">Gérer les rendez-vous →</a>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <p class="text-sm text-ink/60">Conversations non lues</p>
        <p class="text-3xl font-extrabold <?= (int) $unreadThreads > 0 ? 'text-gold' : 'text-brand' ?> mt-1"><?= (int) $unreadThreads ?></p>
        <a href="<?= e(Router::url('admin.messages')) ?>" class="text-xs text-brand hover:underline">Ouvrir la messagerie →</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-brand/10 overflow-hidden">
        <div class="px-5 py-4 border-b border-brand/10 flex items-center justify-between">
            <h2 class="font-bold">Derniers dossiers</h2>
            <a href="<?= e(Router::url('admin.projects')) ?>" class="text-xs text-brand hover:underline">Tout afficher →</a>
        </div>
        <table class="w-full text-sm">
            <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
                <tr>
                    <th class="px-5 py-3">Référence</th>
                    <th class="px-5 py-3">Projet</th>
                    <th class="px-5 py-3 hidden md:table-cell">Client</th>
                    <th class="px-5 py-3">Statut</th>
                    <th class="px-5 py-3 text-right">→</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $project): ?>
                <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                    <td class="px-5 py-3 font-mono text-xs"><?= e($project['reference']) ?></td>
                    <td class="px-5 py-3 font-medium"><?= e($project['name']) ?></td>
                    <td class="px-5 py-3 hidden md:table-cell text-ink/60">
                        <?= e($project['locality'] ?: $project['type']) ?>
                    </td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            <?= $project['status'] === 'en_cours' ? 'bg-emerald-50 text-emerald-brand' : ($project['status'] === 'bloque' ? 'bg-red-50 text-red-700' : ($project['status'] === 'termine' ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-ink/60')) ?>">
                            <?= e(WorkflowService::formatLabel($project['status'])) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="<?= e(Router::url('admin.projects.show', ['publicId' => $project['public_id']])) ?>" class="text-xs text-brand font-semibold hover:underline">Ouvrir →</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <h2 class="font-bold mb-3">Demandes par statut</h2>
        <ul class="space-y-3 text-sm">
            <?php foreach ($requestCounts as $status => $count): ?>
            <li class="flex items-center justify-between">
                <span class="text-ink/70"><?= e(WorkflowService::formatLabel($status)) ?></span>
                <span class="font-bold px-2.5 py-1 rounded-full bg-brand/10 text-brand"><?= (int) $count ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="mt-5 pt-4 border-t border-brand/10">
            <p class="text-sm text-ink/60">Équipe</p>
            <p class="text-2xl font-extrabold text-brand mt-1"><?= (int) $counselorCount ?></p>
            <p class="text-xs text-ink/45">conseiller<?= (int) $counselorCount > 1 ? 's' : '' ?></p>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>