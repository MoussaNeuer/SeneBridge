<?php
use App\Support\Router;
$this->layout('layouts/admin');
$this->section('title'); ?>Clients<?php $this->endSection();
$this->section('content');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Clients</h1>
        <p class="text-sm text-ink/60 mt-1"><?= (int) $pagination['total'] ?> compte<?= (int) $pagination['total'] > 1 ? 's' : '' ?> client.</p>
    </div>
    <a href="<?= e(Router::url('admin.clients')) ?>" class="px-4 py-2 rounded-lg border border-brand/15 text-sm hover:bg-cream transition">Réinitialiser</a>
</div>

<form method="GET" action="<?= e(Router::url('admin.clients')) ?>" class="bg-white rounded-2xl border border-brand/10 p-4 mb-6 flex flex-wrap items-end gap-4">
    <div class="flex-1 min-w-[240px]">
        <label for="q" class="text-xs font-medium text-ink/60 block mb-1">Rechercher (nom, e-mail)</label>
        <input id="q" name="q" value="<?= e($term) ?>" placeholder="Ex. Moussa, contact@exemple.sn…"
               class="w-full px-3 py-2 rounded-lg border border-brand/15 text-sm">
    </div>
    <button type="submit" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition">Rechercher</button>
</form>

<div class="bg-white rounded-2xl border border-brand/10 overflow-x-auto">
    <table class="w-full text-sm min-w-[720px]">
        <thead class="text-left text-xs text-ink/50 uppercase border-b border-brand/10">
            <tr>
                <th class="px-5 py-3">Nom</th>
                <th class="px-5 py-3">E-mail</th>
                <th class="px-5 py-3 hidden md:table-cell">Téléphone</th>
                <th class="px-5 py-3 hidden lg:table-cell">Créé le</th>
                <th class="px-5 py-3 text-right">→</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagination['items'] as $client): ?>
            <tr class="border-b border-brand/5 last:border-0 hover:bg-cream/50">
                <td class="px-5 py-3 font-medium"><?= e(trim($client['first_name'] . ' ' . $client['last_name'])) ?></td>
                <td class="px-5 py-3"><?= e($client['email']) ?></td>
                <td class="px-5 py-3 hidden md:table-cell text-ink/60"><?= e($client['phone'] ?: '—') ?></td>
                <td class="px-5 py-3 hidden lg:table-cell text-ink/60"><?= e(date('d/m/Y', strtotime($client['created_at']))) ?></td>
                <td class="px-5 py-3 text-right">
                    <a href="<?= e(Router::url('admin.clients.show', ['publicId' => $client['public_id']])) ?>" class="text-xs text-brand font-semibold hover:underline">Ouvrir →</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pagination['items'] === []): ?>
        <p class="text-sm text-ink/60 text-center py-8">Aucun client trouvé.</p>
    <?php endif; ?>
</div>
<?php if ($term === ''): ?>
    <?php $this->include('admin/partials/pagination', ['pagination' => $pagination]); ?>
<?php endif; ?>

<?php $this->endSection(); ?>