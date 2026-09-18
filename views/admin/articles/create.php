<?php
use App\Support\App;
use App\Support\CSRF;
$this->layout('layouts/admin');
$this->section('title'); ?><?= isset($article) ? 'Modifier l\'article' : 'Nouvel article' ?><?php $this->endSection();
$this->section('content');
$article = $article ?? null;
?>

<h1 class="text-2xl font-extrabold mb-6"><?= $article ? 'Modifier l\'article' : 'Nouvel article' ?></h1>

<form method="POST" action="<?= $article ? e(route('admin.articles.update', ['publicId' => $article['public_id']])) : e(route('admin.articles.store')) ?>"
      class="bg-white rounded-2xl border border-brand/10 p-6 max-w-3xl">
    <?= CSRF::field() ?>

    <div class="space-y-4">
        <div>
            <label for="title" class="text-sm font-medium block">Titre *</label>
            <input id="title" name="title" required value="<?= e(App::old('title', $article['title'] ?? '')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30">
        </div>
        <div>
            <label for="excerpt" class="text-sm font-medium block">Chapeau / résumé</label>
            <input id="excerpt" name="excerpt" value="<?= e(App::old('excerpt', $article['excerpt'] ?? '')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div>
            <label for="body" class="text-sm font-medium block">Contenu *</label>
            <textarea id="body" name="body" rows="10" required class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15 font-mono text-sm"><?= e(App::old('body', $article['body'] ?? '')) ?></textarea>
            <p class="text-xs text-ink/45 mt-1">Texte brut ; les retours à la ligne sont conservés.</p>
        </div>
        <div>
            <label for="cover_url" class="text-sm font-medium block">URL de couverture</label>
            <input id="cover_url" name="cover_url" placeholder="https://…/image.jpg" value="<?= e(App::old('cover_url', $article['cover_url'] ?? '')) ?>"
                   class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <div>
            <label for="status" class="text-sm font-medium block">Statut</label>
            <select id="status" name="status" class="mt-1 w-full px-3 py-2 rounded-lg border border-brand/15">
                <option value="brouillon" <?= ($article['status'] ?? '') === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                <option value="publie" <?= ($article['status'] ?? '') === 'publie' ? 'selected' : '' ?>>Publié</option>
            </select>
        </div>
        <?php if ($article): ?>
            <p class="text-xs text-ink/50">Slug actuel : <code><?= e($article['slug']) ?></code> · Auteur : #<?= (int) $article['author_id'] ?></p>
        <?php endif; ?>
    </div>

    <button type="submit" class="mt-6 px-6 py-3 rounded-xl bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">
        <?= $article ? 'Enregistrer les modifications' : 'Créer l\'article' ?>
    </button>
</form>

<?php $this->endSection(); ?>