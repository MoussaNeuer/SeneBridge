<?php
$this->layout('layouts/app');
$this->section('title'); ?>À propos — SeneBridge<?php $this->endSection();
$this->section('content');
?>

<section class="bg-brand text-brand-foreground">
    <div class="max-w-7xl mx-auto px-4 py-14 text-center">
        <p class="text-sm tracking-widest uppercase opacity-70">Qui sommes-nous</p>
        <h1 class="text-3xl md:text-5xl font-extrabold mt-2">Le pont entre la diaspora et le Sénégal</h1>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 py-12 grid gap-10 lg:grid-cols-2 items-start">
    <div class="bg-white rounded-2xl border border-brand/10 p-6 md:p-8">
        <h2 class="text-2xl font-extrabold">Notre mission</h2>
        <p class="mt-3 text-sm text-ink/70 leading-relaxed">
            SeneBridge accompagne la diaspora sénégalaise dans la réalisation de ses projets au pays :
            achat immobilier, pilotage de travaux, import/export, acquisition de véhicules, conciergerie
            ou investissement.
        </p>
        <p class="mt-3 text-sm text-ink/70 leading-relaxed">
            Chaque dossier dispose d'un suivi transparent : vous voyez en temps réel où se trouve votre
            projet, par qui il est traité et ce qui reste à faire.
        </p>
        <p class="mt-3 text-sm text-ink/70 leading-relaxed">
            Une équipe sur place à Dakar, des partenaires de confiance, et la technologie pour garder le lien.
        </p>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <?php foreach ($values as $value): ?>
        <div class="bg-white rounded-2xl border border-brand/10 p-6">
            <h3 class="font-bold"><?= e($value['title']) ?></h3>
            <p class="text-sm text-ink/65 mt-2 leading-relaxed"><?= e($value['description']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 pb-12 text-center">
    <a href="<?= e(route('pages.start-project')) ?>"
       class="inline-block px-6 py-3 rounded-xl bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Commencer avec nous</a>
</div>

<?php $this->endSection(); ?>