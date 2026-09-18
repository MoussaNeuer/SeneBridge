<?php $this->layout('layouts/app'); ?>
<?php $this->section('title'); ?>Accueil<?php $this->endSection(); ?>
<?php $this->section('content'); ?>

<!-- Hero -->
<section class="bg-brand text-brand-foreground">
    <div class="max-w-7xl mx-auto px-4 py-20 md:py-28 grid gap-10 md:grid-cols-2 items-center">
        <div>
            <p class="inline-block px-3 py-1 rounded-full bg-white/10 text-sm font-medium mb-5">Plateforme digitale · Dakar & Diaspora</p>
            <h1 class="text-4xl md:text-5xl font-extrabold leading-tight tracking-tight">
                Le pont entre la <span class="text-gold">diaspora</span> et le <span class="text-gold">Sénégal</span>
            </h1>
            <p class="mt-5 text-lg opacity-90 leading-relaxed">
                Suivez vos projets immobilier, import/export ou de conciergerie en toute transparence,
                où que vous soyez dans le monde.
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="<?= e(app_url('register')) ?>"
                   class="px-6 py-3 rounded-lg bg-gold text-brand font-semibold hover:bg-gold-light transition">
                    Découvrir nos services
                </a>
                <a href="#apropos"
                   class="px-6 py-3 rounded-lg border border-white/30 hover:bg-white/10 transition">
                    ▶ Voir la vidéo
                </a>
            </div>
        </div>
        <div class="rounded-2xl bg-white/10 backdrop-blur p-8 hidden md:block">
            <div class="grid gap-4">
                <div class="flex items-start gap-3">
                    <span class="w-10 h-10 rounded-lg bg-gold text-brand grid place-items-center font-bold">1</span>
                    <p class="text-sm opacity-90">Créez votre compte client en quelques minutes.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="w-10 h-10 rounded-lg bg-gold text-brand grid place-items-center font-bold">2</span>
                    <p class="text-sm opacity-90">Déposez votre projet ou votre recherche de bien.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="w-10 h-10 rounded-lg bg-gold text-brand grid place-items-center font-bold">3</span>
                    <p class="text-sm opacity-90">Suivez chaque étape, vos documents et vos paiements.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services -->
<section id="services" class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <p class="text-gold font-semibold text-sm uppercase tracking-widest">Nos services</p>
        <h2 class="text-3xl md:text-4xl font-extrabold mt-2">Un accompagnement complet, de Dakar à votre écran</h2>
    </div>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($services as $service): ?>
        <div class="bg-white rounded-2xl border border-brand/10 p-6 hover:shadow-lg transition">
            <div class="w-12 h-12 rounded-xl bg-brand/10 grid place-items-center text-brand font-bold mb-4">
                <?= e(mb_strtoupper(mb_substr($service['title'], 0, 1))) ?>
            </div>
            <h3 class="text-lg font-bold"><?= e($service['title']) ?></h3>
            <p class="mt-2 text-sm opacity-75 leading-relaxed"><?= e($service['description']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- À propos / confiance -->
<section id="apropos" class="bg-white">
    <div class="max-w-7xl mx-auto px-4 py-16 grid gap-10 md:grid-cols-2 items-center">
        <div>
            <p class="text-gold font-semibold text-sm uppercase tracking-widest">À propos</p>
            <h2 class="text-3xl font-extrabold mt-2">L'expertise locale, la sérénité de la diaspora</h2>
            <p class="mt-4 leading-relaxed opacity-80">
                SeneBridge centralise la gestion de vos dossiers : biens, projets, documents, factures et échanges.
                Chaque étape est tracée et visible dans votre espace client, pour une transparence totale.
            </p>
            <div class="mt-6 flex gap-8">
                <div>
                    <p class="text-3xl font-extrabold text-brand">100%</p>
                    <p class="text-sm opacity-70">Transparence sur vos dossiers</p>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-brand">24/7</p>
                    <p class="text-sm opacity-70">Accès à votre espace client</p>
                </div>
            </div>
        </div>
        <div class="bg-brand rounded-2xl p-8 text-brand-foreground">
            <h3 class="text-xl font-bold mb-4">L'espace client : votre tableau de bord</h3>
            <ul class="space-y-3 text-sm opacity-90">
                <li>✔ Suivi de la timeline de vos projets</li>
                <li>✔ Photos, vidéos et documents centralisés</li>
                <li>✔ Factures et paiements à jour</li>
                <li>✔ Messagerie directe avec votre conseiller</li>
            </ul>
            <a href="<?= e(app_url('login')) ?>" class="mt-6 inline-block px-5 py-2.5 rounded-lg bg-gold text-brand font-semibold hover:bg-gold-light transition">
                Accéder à l'espace client
            </a>
        </div>
    </div>
</section>

<!-- Contact -->
<section id="contact" class="max-w-7xl mx-auto px-4 py-16 text-center">
    <h2 class="text-3xl font-extrabold">Prêt à démarrer votre projet ?</h2>
    <p class="mt-3 opacity-75">Contactez-nous ou créez votre compte pour être accompagné dès aujourd'hui.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-4">
        <a href="<?= e(app_url('register')) ?>" class="px-6 py-3 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Créer un compte</a>
        <a href="mailto:contact@senebridge.sn" class="px-6 py-3 rounded-lg border border-brand text-brand font-semibold hover:bg-brand/5 transition">Contactez-nous</a>
    </div>
</section>

<?php $this->endSection(); ?>