<?php
/* Fichier partiel : grille de cases à cocher des permissions.
 * Attend : $groups (module => permissions), $selected (array<int,int>|null), $fieldName.
 */
$fieldName = $fieldName ?? 'permissions[]';
$selected = $selected ?? [];
$selected = array_map('intval', $selected);
?>
<div class="grid gap-5 md:grid-cols-2">
    <?php foreach ($groups as $module => $permissions): ?>
    <fieldset class="border border-brand/10 rounded-xl p-4">
        <legend class="px-2 text-sm font-bold uppercase tracking-wide text-brand"><?= e($module) ?></legend>
        <ul class="space-y-2">
            <?php foreach ($permissions as $permission): ?>
            <li>
                <label class="flex items-start gap-2 text-sm cursor-pointer">
                    <input type="checkbox" name="<?= e($fieldName) ?>" value="<?= (int) $permission['id'] ?>"
                           class="mt-0.5 accent-brand" <?= in_array((int) $permission['id'], $selected, true) ? 'checked' : '' ?>>
                    <span>
                        <span class="font-medium"><?= e((string) $permission['label']) ?></span>
                        <span class="block font-mono text-[11px] text-ink/50"><?= e((string) $permission['name']) ?></span>
                    </span>
                </label>
            </li>
            <?php endforeach; ?>
        </ul>
    </fieldset>
    <?php endforeach; ?>
</div>