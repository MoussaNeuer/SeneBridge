<?php
$this->layout('layouts/app');
$this->section('title'); ?>Nos services — SeneBridge<?php $this->endSection();
$this->section('content');
?>

<section class="bg-brand text-brand-foreground">
    <div class="max-w-7xl mx-auto px-4 py-14 text-center">
        <p class="text-sm tracking-widest uppercase opacity-70">Ce que nous faisons</p>
        <h1 class="text-3xl md:text-5xl font-extrabold mt-2">Nos services, votre tranquillité d'esprit</h1>
        <p class="mt-3 text-brand-foreground/80 max-w-2xl mx-auto">
            Immobilier, gestion de projets, import/export… Un conseiller dédié et un suivi transparent,
            où que vous soyez.
        </p>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 py-12">
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($services as $service): ?>
        <div class="bg-white rounded-2xl border border-brand/10 p-6 hover:shadow-lg transition flex flex-col">
            <span class="text-3xl"><?= e($service['icon']) ?></span>
            <h2 class="text-xl font-bold mt-3"><?= e($service['title']) ?></h2>
            <p class="text-sm text-ink/65 mt-2 leading-relaxed flex-1"><?= e($service['description']) ?></p>
            <a href="<?= e(route('pages.start-project')) ?>" class="mt-4 text-brand font-semibold text-sm hover:underline">Démarrer un projet →</a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-12 bg-gradient-to-br from-brand to-brand-dark rounded-3xl text-brand-foreground p-8 md:p-12 flex flex-wrap items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-extrabold">Un projet en tête ?</h2>
            <p class="text-brand-foreground/80 mt-1">Décrivez-nous votre besoin, nous nous occupons du reste.</p>
        </div>
        <a href="<?= e(route('pages.start-project')) ?>"
           class="px-6 py-3 rounded-xl bg-gold text-brand font-semibold hover:bg-gold-light transition">Démarrer un projet</a>
    </div>
</div>

<?php $this->endSection(); ?>