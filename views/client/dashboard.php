<?php
use App\Support\Gate;
use App\Support\Router;
use App\Services\WorkflowService;
$this->layout('layouts/client');
$this->section('title'); ?>Tableau de bord<?php $this->endSection();
$this->section('content');
$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Bonjour<?= $displayName !== '' ? ', ' . e($displayName) : '' ?> 👋</h1>
        <p class="text-sm text-ink/60 mt-1">Suivez vos dossiers et leur avancement en temps réel.</p>
    </div>
    <a href="<?= e(route('pages.start-project')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">
        + Démarrer un nouveau projet
    </a>
</div>

<?php if ($projects === []): ?>
    <div class="bg-white rounded-2xl border border-brand/10 p-10 text-center">
        <p class="text-3xl">🚀</p>
        <h2 class="mt-3 text-lg font-bold">Aucun dossier pour le moment</h2>
        <p class="mt-1 text-sm text-ink/60 max-w-md mx-auto">
            Lancez votre premier projet (immobilier, gestion de projets, import/export…) et notre équipe vous contacte rapidement.
        </p>
        <a href="<?= e(route('pages.start-project')) ?>" class="mt-5 inline-block px-5 py-2.5 rounded-lg bg-gold text-brand font-semibold hover:bg-gold-light transition">
            Démarrer un projet
        </a>
    </div>
<?php else: ?>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        <div class="bg-white rounded-2xl border border-brand/10 p-5">
            <p class="text-sm text-ink/60">Dossiers actifs</p>
            <p class="text-3xl font-extrabold text-brand mt-1"><?= (int) $activeCount ?></p>
        </div>
        <div class="bg-white rounded-2xl border border-brand/10 p-5">
            <p class="text-sm text-ink/60">Total de mes dossiers</p>
            <p class="text-3xl font-extrabold text-brand mt-1"><?= (int) $totalCount ?></p>
        </div>
        <div class="bg-white rounded-2xl border border-brand/10 p-5">
            <p class="text-sm text-ink/60">Notifications non lues</p>
            <p class="text-3xl font-extrabold text-brand mt-1"><?= (int) $unreadCount ?></p>
            <a href="<?= e(Router::url('client.notifications')) ?>" class="text-xs text-brand hover:underline">Tout afficher →</a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-brand/10 overflow-hidden">
        <div class="px-5 py-4 border-b border-brand/10 flex items-center justify-between">
            <h2 class="font-bold">Mes dossiers (<?= count($projects) ?>)</h2>
            <a href="<?= e(Router::url('client.projects')) ?>" class="text-xs text-brand hover:underline">Voir tout →</a>
        </div>
        <table class="w-full text-sm">
            <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
                <tr>
                    <th class="px-5 py-3">Référence</th>
                    <th class="px-5 py-3">Projet</th>
                    <th class="px-5 py-3 hidden md:table-cell">Statut</th>
                    <th class="px-5 py-3">Avancement</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($projects, 0, 5) as $project): ?>
                <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                    <td class="px-5 py-3 font-mono text-xs"><?= e($project['reference']) ?></td>
                    <td class="px-5 py-3 font-medium"><?= e($project['name']) ?></td>
                    <td class="px-5 py-3 hidden md:table-cell">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            <?= $project['status'] === 'en_cours' ? 'bg-emerald-50 text-emerald-brand' : ($project['status'] === 'bloque' ? 'bg-red-50 text-red-700' : ($project['status'] === 'termine' ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-ink/60')) ?>">
                            <?= e(WorkflowService::formatLabel($project['status'])) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-24 h-1.5 rounded bg-brand/10">
                                <div class="h-1.5 rounded bg-brand" style="width: <?= (int) ($progress[(int) $project['id']] ?? 0) ?>%"></div>
                            </div>
                            <span class="text-xs text-ink/60"><?= (int) ($progress[(int) $project['id']] ?? 0) ?>%</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="<?= e(Router::url('client.projects.show', ['publicId' => $project['public_id']])) ?>"
                           class="text-xs text-brand font-semibold hover:underline">Suivre →</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($latestNotifications !== []): ?>
    <div class="mt-8 bg-white rounded-2xl border border-brand/10 p-5">
        <h2 class="font-bold mb-3">Dernières notifications</h2>
        <ul class="divide-y divide-brand/5">
            <?php foreach ($latestNotifications as $notification): ?>
            <li class="py-3 flex items-start gap-3">
                <span class="text-lg"><?= $notification['is_read'] ? '🔸' : '🔔' ?></span>
                <div>
                    <p class="text-sm font-medium"><?= e($notification['title']) ?></p>
                    <?php if ($notification['body']): ?>
                        <p class="text-xs text-ink/60 mt-0.5"><?= e($notification['body']) ?></p>
                    <?php endif; ?>
                    <p class="text-[11px] text-ink/40 mt-1"><?= e(date('d/m/Y H:i', strtotime($notification['created_at']))) ?></p>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

<?php endif; ?>

<?php $this->endSection(); ?>
<?php unset($displayName); ?>