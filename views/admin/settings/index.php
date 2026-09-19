<?php
$this->layout('layouts/admin');
$this->section('title'); ?>Paramètres<?php $this->endSection();
$this->section('content');
?>
<div class="max-w-5xl mx-auto">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-extrabold">Paramètres de la plateforme</h1>
            <p class="text-sm text-ink/50 mt-1">Modifiez l'identité, la monnaie, la messagerie, la sécurité et les options de la plateforme. Les changements prennent effet immédiatement, sans redéploiement.</p>
        </div>
    </div>

    <?php if ($sections == []): ?>
        <div class="mt-8">
            <?php $this->insert('admin/partials/empty', [
                'icon'   => 'settings',
                'message' => 'Aucun paramètre enregistré pour le moment.',
            ]) ?>
        </div>
    <?php endif; ?>

    <?php if (($sections ?? []) !== []): ?>
    <form method="POST" action="<?= e(route('admin.settings.update')) ?>" class="mt-6 space-y-6">
        <?= App\Support\CSRF::field() ?>

        <?php foreach ($sections as $section => $rows): ?>
            <?php if ($rows === []): continue; endif; ?>
            <section class="bg-white rounded-2xl border border-brand/10 shadow-sm overflow-hidden">
                <header class="flex items-center justify-between px-5 py-3.5 border-b border-brand/10 bg-cream/60">
                    <h2 class="text-base font-extrabold"><?= e($labels[$section] ?? ucfirst($section)) ?></h2>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-ink/40"><?= count($rows) ?> réglage(s)</span>
                </header>
                <div class="divide-y divide-brand/5">
                    <?php foreach ($rows as $key => $row): ?>
                        <?php
                        $label = (string) ($row['label'] ?? $key);
                        $desc  = (string) ($row['description'] ?? '');
                        $type  = (string) ($row['type'] ?? 'string');
                        $value = (string) ($row['value'] ?? '');
                        ?>
                        <div class="px-5 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <label for="setting-<?= e($key) ?>" class="block text-sm font-bold"><?= e($label) ?></label>
                                    <?php if ($desc !== ''): ?>
                                        <p class="text-xs text-ink/50 mt-0.5"><?= e($desc) ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="shrink-0 w-full sm:w-[240px]">
                                    <?php if ($type === 'bool'): ?>
                                        <label class="inline-flex items-center gap-2.5 cursor-pointer">
                                            <input type="hidden" name="<?= e($key) ?>" value="0">
                                            <input type="checkbox" id="setting-<?= e($key) ?>" name="<?= e($key) ?>" value="1"
                                                   class="sb-toggle" <?= $value === '1' ? 'checked' : '' ?>>
                                            <span class="text-sm"><?= $value === '1' ? 'Activé' : 'Désactivé' ?></span>
                                        </label>
                                    <?php elseif ($type === 'secret'): ?>
                                        <input type="password" id="setting-<?= e($key) ?>" name="<?= e($key) ?>"
                                               value="" autocomplete="new-password" placeholder="••••••••"
                                               class="w-full rounded-lg border border-brand/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold/60">
                                        <p class="text-[11px] text-ink/40 mt-1">Laisser vide pour conserver la valeur actuelle.</p>
                                    <?php elseif ($type === 'email'): ?>
                                        <input type="email" id="setting-<?= e($key) ?>" name="<?= e($key) ?>"
                                               value="<?= e($value) ?>"
                                               class="w-full rounded-lg border border-brand/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold/60">
                                    <?php elseif ($type === 'url'): ?>
                                        <input type="url" id="setting-<?= e($key) ?>" name="<?= e($key) ?>"
                                               value="<?= e($value) ?>"
                                               class="w-full rounded-lg border border-brand/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold/60">
                                    <?php elseif ($type === 'int'): ?>
                                        <input type="number" step="1" id="setting-<?= e($key) ?>" name="<?= e($key) ?>"
                                               value="<?= e($value) ?>"
                                               class="w-full rounded-lg border border-brand/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold/60">
                                    <?php elseif ($type === 'timezone'): ?>
                                        <select id="setting-<?= e($key) ?>" name="<?= e($key) ?>"
                                                class="w-full rounded-lg border border-brand/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold/60">
                                            <?php $tz = $value !== '' ? $value : (string) config('app.timezone', 'Africa/Dakar'); ?>
                                            <?php foreach (timezone_identifiers_list() as $identifier): ?>
                                                <option value="<?= e($identifier) ?>" <?= $identifier === $tz ? 'selected' : '' ?>><?= e($identifier) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" id="setting-<?= e($key) ?>" name="<?= e($key) ?>"
                                               value="<?= e($value) ?>"
                                               class="w-full rounded-lg border border-brand/15 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold/60">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="sticky bottom-4 flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand text-brand-foreground px-5 py-2.5 text-sm font-bold shadow hover:opacity-90 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                Enregistrer les paramètres
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>
