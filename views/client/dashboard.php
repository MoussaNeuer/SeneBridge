<?php
use App\Support\App;
$this->layout('layouts/app');
$this->section('title'); ?>Mon espace client<?php $this->endSection();
$this->section('content');
$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
?>

<section class="bg-brand text-brand-foreground">
    <div class="max-w-7xl mx-auto px-4 py-10 flex flex-wrap items-center justify-between gap-6">
        <div>
            <p class="text-sm opacity-80">Espace client SeneBridge</p>
            <h1 class="text-3xl md:text-4xl font-extrabold mt-1">
                Bienvenue<?= $displayName !== '' ? ', ' . e($displayName) : '' ?> 👋
            </h1>
        </div>
        <form method="POST" action="<?= e(app_url('logout')) ?>" class="inline">
            <?= \App\Support\CSRF::field() ?>
            <button type="submit"
                    class="px-4 py-2 rounded-lg border border-white/30 hover:bg-white/10 transition text-sm">
                Se déconnecter
            </button>
        </form>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 py-10">

    <div class="grid gap-6 md:grid-cols-3 mb-10">
        <div class="bg-white rounded-2xl border border-brand/10 p-6">
            <p class="text-sm opacity-60">Mot de passe</p>
            <p class="text-lg font-bold mt-1">En cours de sécurisation</p>
            <p class="text-xs opacity-60 mt-1">Gestion du mot de passe en Phase 5.</p>
        </div>
        <div class="bg-white rounded-2xl border border-brand/10 p-6">
            <p class="text-sm opacity-60">Adresse e-mail</p>
            <p class="text-lg font-bold mt-1 truncate"><?= e($user['email'] ?? '') ?></p>
            <p class="text-xs <?= empty($user['email_verified_at']) ? 'text-amber-600' : 'text-emerald-brand' ?> 60 mt-1">
                <?= empty($user['email_verified_at']) ? 'Non vérifiée — vérifiez votre boîte mail.' : 'Adresse vérifiée ✔' ?>
            </p>
        </div>
        <div class="bg-white rounded-2xl border border-brand/10 p-6">
            <p class="text-sm opacity-60">Téléphone</p>
            <p class="text-lg font-bold mt-1"><?= e($user['phone'] ?? 'Non renseigné') ?></p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-brand/10 p-8 text-center">
        <div class="w-14 h-14 rounded-full bg-brand/10 grid place-items-center text-brand font-bold mx-auto mb-4">🔧</div>
        <h2 class="text-xl font-extrabold">Prolongez vos projets ici</h2>
        <p class="mt-2 text-sm opacity-70 max-w-xl mx-auto leading-relaxed">
            Le tableau de bord complet arrive prochainement : dépôt de projets, timeline des étapes,
            documents et médias, messagerie, factures et paiements (Phases 5 à 8 de la feuille de route).
        </p>
        <a href="<?= e(app_url('/')) ?>" class="mt-6 inline-block px-5 py-2.5 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">
            Retour à l'accueil
        </a>
    </div>

</div>

<?php $this->endSection(); ?>