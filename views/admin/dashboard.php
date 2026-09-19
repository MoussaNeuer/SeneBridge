<?php
use App\Models\Notification;
use App\Services\WorkflowService;
use App\Support\Router;
use App\Support\App;
$this->layout('layouts/admin');
$this->section('title'); ?>Tableau de bord<?php $this->endSection();
$this->section('subtitle'); ?>Pilotage de l'activité SeneBridge en temps réel<?php $this->endSection();
$this->section('content');
$user = $user ?? App::user();
$notifUnread = $user !== null ? Notification::countUnread((int) $user['id']) : 0;
$donutData = [
    ['label' => 'En cours', 'value' => (int) ($stats['en_cours'] ?? 0), 'color' => '#005B4F'],
    ['label' => 'Bloqué', 'value' => (int) ($stats['bloque'] ?? 0), 'color' => '#DC2626'],
    ['label' => 'Terminé', 'value' => (int) ($stats['termine'] ?? 0), 'color' => '#087F6A'],
    ['label' => 'Archivé', 'value' => (int) ($stats['archive'] ?? 0), 'color' => '#6B7280'],
];
$barData = array_map(static fn (array $m): array => [
    'label' => $m['label'] ?? '',
    'value' => (float) ($m['total'] ?? 0),
    'display' => format_number($m['total'] ?? 0) . ' F',
], (array) $monthlyRevenue);
$maxCounselor = max(1, ...array_map(static fn (array $c): int => (int) ($c['projects_count'] ?? 0), (array) $topCounselors));
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold flex items-center gap-3">Tableau de bord
            <span class="sb-live-dot inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-brand bg-emerald-50 px-2 py-1 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> EN DIRECT
            </span>
        </h1>
    </div>
    <div class="flex gap-3">
        <a href="<?= e(Router::url('admin.projects.create')) ?>" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">+ Nouveau dossier</a>
        <a href="<?= e(Router::url('admin.projects.kanban')) ?>" class="px-4 py-2 rounded-lg border border-brand/15 text-sm hover:bg-cream dark:hover:bg-white/10 transition">Pipeline →</a>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <div class="bg-white rounded-2xl border border-brand/10 p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-ink/60">Dossiers en cours</p>
                <p class="text-3xl font-extrabold text-brand mt-1"><?= (int) ($stats['en_cours'] ?? 0) ?></p>
                <p class="text-xs text-ink/45 mt-1"><?= (int) ($stats['bloque'] ?? 0) ?> bloqués · <?= (int) ($stats['termine'] ?? 0) ?> terminés</p>
            </div>
            <span class="grid place-items-center w-11 h-11 rounded-xl bg-brand/10 text-brand"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-ink/60">Clients</p>
                <p class="text-3xl font-extrabold text-brand mt-1"><?= (int) $clientCount ?></p>
                <a href="<?= e(Router::url('admin.clients')) ?>" class="text-xs text-brand hover:underline">Gérer les comptes →</a>
            </div>
            <span class="grid place-items-center w-11 h-11 rounded-xl bg-brand/10 text-brand"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5M16 4.5a3.5 3.5 0 0 1 0 7M18 15c2.5.5 4 2 4 4.5"/></svg></span>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-ink/60">Nouvelles demandes</p>
                <p class="text-3xl font-extrabold <?= $newRequests > 0 ? 'text-gold' : 'text-brand' ?> mt-1"><?= (int) $newRequests ?></p>
                <a href="<?= e(Router::url('admin.requests')) ?>" class="text-xs text-brand hover:underline">Voir les demandes →</a>
            </div>
            <span class="grid place-items-center w-11 h-11 rounded-xl bg-gold/15 text-gold"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 4h16v12H8l-4 4z"/></svg></span>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-ink/60">Messages non lus</p>
                <p class="text-3xl font-extrabold text-brand mt-1"><?= (int) $notifUnread + (int) $unreadContacts ?></p>
                <a href="<?= e(Router::url('admin.messages')) ?>" class="text-xs text-brand hover:underline">Messagerie →</a>
            </div>
            <span class="grid place-items-center w-11 h-11 rounded-xl bg-brand/10 text-brand"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M10.3 21a2 2 0 0 0 3.4 0"/></svg></span>
        </div>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <div class="bg-white rounded-2xl border border-brand/10 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <p class="text-sm text-ink/60">CA encaissé (validé)</p>
            <svg class="w-4 h-4 text-emerald-brand" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16"/></svg>
        </div>
        <p class="text-2xl font-extrabold text-emerald-brand mt-1"><?= format_number($finance['collected']) ?> F</p>
        <div class="h-9 mt-2" data-chart="sparkline"><script type="application/json"><?= json_encode(['values' => array_map('intval', $monthlyPayments), 'color' => '#087F6A']) ?></script></div>
        <p class="text-xs text-ink/45 mt-1"><?= format_number($finance['gross']) ?> F facturés au total</p>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5 shadow-sm">
        <p class="text-sm text-ink/60">À recouvrer</p>
        <p class="text-2xl font-extrabold <?= $finance['outstanding'] > 0 ? 'text-gold' : 'text-brand' ?> mt-1"><?= format_number($finance['outstanding']) ?> F</p>
        <a href="<?= e(Router::url('admin.invoices')) ?>" class="text-xs text-brand hover:underline">Suivre le recouvrement →</a>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5 shadow-sm">
        <p class="text-sm text-ink/60">Taux de recouvrement</p>
        <p class="text-2xl font-extrabold text-brand mt-1"><?= format_number($finance['recovery_rate'], 1) ?> %</p>
        <div class="mt-2 h-1.5 rounded-full bg-brand/10 overflow-hidden">
            <div class="h-full rounded-full bg-emerald-brand transition-all" style="width: <?= min(100, (float) $finance['recovery_rate']) ?>%"></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-brand/10 p-5 shadow-sm">
        <p class="text-sm text-ink/60">Paiements à valider</p>
        <p class="text-2xl font-extrabold <?= ($paymentStats['en_cours'] ?? 0) > 0 ? 'text-gold' : 'text-brand' ?> mt-1"><?= (int) ($paymentStats['en_cours'] ?? 0) ?></p>
        <p class="text-xs text-ink/45 mt-1"><?= format_number($paymentStats['montant_valide'] ?? 0) ?> F déjà validés</p>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3 mb-6">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-brand/10 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-lg">CA encaissé — 12 derniers mois</h2>
            <a href="<?= e(Router::url('admin.payments')) ?>" class="text-xs text-brand hover:underline">Paiements →</a>
        </div>
        <div data-chart="bars" class="w-full"><script type="application/json"><?= json_encode($barData) ?></script></div>
    </div>

    <div class="bg-white rounded-2xl border border-brand/10 p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between mb-2">
            <h2 class="font-bold text-lg">Répartition des dossiers</h2>
            <span class="text-xs text-ink/50"><?= (int) ($stats['total'] ?? 0) ?> au total</span>
        </div>
        <div data-chart="donut" class="w-full h-44"><script type="application/json"><?= json_encode($donutData) ?></script></div>
        <ul class="mt-3 space-y-1.5 text-xs">
            <?php foreach ($donutData as $d): ?>
            <li class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-ink/70"><span class="w-2 h-2 rounded-full inline-block" style="background: <?= e($d['color']) ?>"></span><?= e($d['label']) ?></span>
                <span class="font-bold"><?= (int) $d['value'] ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-brand/10 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-brand/10 flex items-center justify-between">
            <h2 class="font-bold">Derniers dossiers</h2>
            <a href="<?= e(Router::url('admin.projects')) ?>" class="text-xs text-brand hover:underline">Tout afficher →</a>
        </div>
        <table class="w-full text-sm" data-table data-title="derniers-dossiers">
            <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
                <tr>
                    <th class="px-5 py-3">Référence</th>
                    <th class="px-5 py-3">Projet</th>
                    <th class="px-5 py-3 hidden md:table-cell">Localité</th>
                    <th class="px-5 py-3">Statut</th>
                    <th class="px-5 py-3 text-right">→</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $project): ?>
                <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50 dark:hover:bg-white/5" data-status="<?= e($project['status']) ?>">
                    <td class="px-5 py-3 font-mono text-xs"><?= e($project['reference']) ?></td>
                    <td class="px-5 py-3 font-medium"><?= e($project['name']) ?></td>
                    <td class="px-5 py-3 hidden md:table-cell text-ink/60"><?= e($project['locality'] ?: $project['type']) ?></td>
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

    <div class="lg:col-span-1 bg-white rounded-2xl border border-brand/10 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-bold">Top conseillers</h2>
            <span class="text-xs text-ink/50">dossiers actifs</span>
        </div>
        <?php if ($topCounselors === []): ?>
            <p class="text-sm text-ink/50">Aucun conseiller pour le moment.</p>
        <?php endif; ?>
        <ul class="space-y-4">
            <?php foreach ($topCounselors as $i => $counselor): ?>
            <li>
                <div class="flex items-center justify-between gap-2">
                    <span class="flex items-center gap-2 min-w-0">
                        <span class="grid place-items-center w-7 h-7 rounded-lg bg-brand/10 text-brand text-xs font-extrabold shrink-0"><?= e($i + 1) ?></span>
                        <span class="truncate text-sm font-semibold"><?= e(trim($counselor['first_name'] . ' ' . $counselor['last_name'])) ?></span>
                    </span>
                    <span class="text-sm font-extrabold text-brand whitespace-nowrap"><?= (int) $counselor['projects_count'] ?></span>
                </div>
                <div class="mt-1 h-1.5 rounded-full bg-brand/10 overflow-hidden">
                    <div class="h-full rounded-full <?= $i === 0 ? 'bg-gold' : 'bg-brand/70' ?>"
                         style="width: <?= (int) round((int) $counselor['projects_count'] / $maxCounselor * 100) ?>%"></div>
                </div>
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

<div class="grid gap-6 lg:grid-cols-2 mt-6">
    <div class="bg-white rounded-2xl border border-brand/10 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-brand/10 flex items-center justify-between">
            <h2 class="font-bold">Derniers paiements validés</h2>
            <a href="<?= e(Router::url('admin.payments')) ?>" class="text-xs text-brand hover:underline">Tout voir →</a>
        </div>
        <ul class="divide-y divide-brand/5">
            <?php if ($recentPayments === []): ?>
                <li class="px-5 py-4 text-sm text-ink/50">Aucun paiement validé pour le moment.</li>
            <?php endif; ?>
            <?php foreach ($recentPayments as $payment): ?>
            <li class="px-5 py-3 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-semibold truncate"><?= e(($payment['client_first_name'] ?? '') . ' ' . ($payment['client_last_name'] ?? '')) ?></p>
                    <p class="text-xs text-ink/50"><?= e($payment['invoice_number'] ?? 'Sans facture') ?> · <?= date('d/m/Y', strtotime((string) $payment['payment_date'])) ?></p>
                </div>
                <span class="text-sm font-extrabold text-emerald-brand whitespace-nowrap"><?= format_number($payment['amount']) ?> F</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="bg-white rounded-2xl border border-brand/10 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-brand/10 flex items-center justify-between">
            <h2 class="font-bold">Prochains rendez-vous confirmés</h2>
            <a href="<?= e(Router::url('admin.appointments')) ?>" class="text-xs text-brand hover:underline">Tout voir →</a>
        </div>
        <ul class="divide-y divide-brand/5">
            <?php if ($upcomingAppointments === []): ?>
                <li class="px-5 py-4 text-sm text-ink/50">Aucun rendez-vous à venir.</li>
            <?php endif; ?>
            <?php foreach ($upcomingAppointments as $appointment): ?>
            <li class="px-5 py-3 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-semibold truncate"><?= e(($appointment['client_first_name'] ?? '') . ' ' . ($appointment['client_last_name'] ?? '')) ?></p>
                    <p class="text-xs text-ink/50">
                        <?= e(($appointment['counselor_first_name'] ?? '') . ' ' . ($appointment['counselor_last_name'] ?? '')) ?> ·
                        <?= date('d/m/Y', strtotime((string) $appointment['requested_date'])) ?> à <?= e(date('H:i', strtotime((string) $appointment['requested_time']))) ?>
                    </p>
                </div>
                <span class="text-xs font-semibold text-brand text-right whitespace-nowrap max-w-[40%] truncate"><?= e($appointment['motive'] ?: 'Rendez-vous') ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php $this->endSection(); ?>