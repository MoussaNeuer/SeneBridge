<?php
use App\Support\CSRF;
use App\Support\Router;
use App\Services\WorkflowService;
$this->layout('layouts/admin');
$this->section('title'); ?>Dossier <?= e($project['reference']) ?><?php $this->endSection();
$this->section('content');
$pct = (int) $progress;
?>

<a href="<?= e(Router::url('admin.projects')) ?>" class="text-sm text-brand hover:underline">← Retour aux dossiers</a>

<div class="bg-white rounded-2xl border border-brand/10 p-6 mt-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-xs text-ink/50"><?= e($project['reference']) ?> · Dossier #<?= (int) $project['id'] ?></p>
            <h1 class="text-2xl font-extrabold mt-1"><?= e($project['name']) ?></h1>
            <p class="text-sm text-ink/60 mt-1">
                <?= e(ucfirst(str_replace('_', ' ', $project['type']))) ?>
                <?= $project['locality'] ? ' · ' . e($project['locality']) : '' ?>
                · Budget <?= $project['budget'] !== null ? format_number($project['budget']) . ' ' . e($project['currency']) : 'n/a' ?>
            </p>
            <?php if ($project['description']): ?>
                <p class="text-sm text-ink/60 mt-3 max-w-3xl leading-relaxed"><?= nl2br(e($project['description'])) ?></p>
            <?php endif; ?>
        </div>
        <div class="text-right">
            <span class="px-3 py-1.5 rounded-full text-sm font-semibold
                <?= $project['status'] === 'en_cours' ? 'bg-emerald-50 text-emerald-brand' : ($project['status'] === 'bloque' ? 'bg-red-50 text-red-700' : ($project['status'] === 'termine' ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-ink/60')) ?>">
                <?= e(WorkflowService::formatLabel($project['status'])) ?>
            </span>
            <p class="text-xs text-ink/50 mt-2">
                Client : <?= e(trim(($project['client_first_name'] ?? '') . ' ' . ($project['client_last_name'] ?? ''))) ?>
            </p>
            <p class="text-xs text-ink/50">
                Conseiller : <?= $project['counselor_id'] ? e(trim(($project['counselor_first_name'] ?? '') . ' ' . ($project['counselor_last_name'] ?? ''))) : '— Non assigné —' ?>
            </p>
        </div>
    </div>

    <div class="mt-6">
        <div class="flex items-center justify-between text-sm text-ink/60 mb-1">
            <span>Avancement global</span>
            <span class="font-bold text-brand"><?= $pct ?>%</span>
        </div>
        <div class="h-2.5 rounded bg-brand/10">
            <div class="h-2.5 rounded bg-brand transition-all" style="width: <?= $pct ?>%"></div>
        </div>
        <p class="text-xs text-ink/50 mt-1"><?= $project['steps_completed'] ?> / <?= $project['steps_count'] ?> étapes terminées</p>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 mt-6 text-sm">
        <div><span class="text-ink/50">Début : </span><span class="font-medium"><?= e(WorkflowService::formatDate($project['start_date'])) ?></span></div>
        <div><span class="text-ink/50">Échéance : </span><span class="font-medium"><?= e(WorkflowService::formatDate($project['expected_end_date'])) ?></span></div>
        <div><span class="text-ink/50">Créé le : </span><span class="font-medium"><?= e(date('d/m/Y H:i', strtotime($project['created_at']))) ?></span></div>
    </div>
</div>

<?php if ($canManage): ?>
<div class="bg-white rounded-2xl border border-brand/10 p-5 mt-6">
    <h2 class="font-bold text-lg mb-4">Affecter un conseiller</h2>
    <form method="POST" action="<?= e(route('admin.projects.counselor', ['publicId' => $project['public_id']])) ?>" class="flex flex-wrap items-end gap-4">
        <?= CSRF::field() ?>
        <div class="flex-1 min-w-[220px]">
            <label for="counselor_id" class="text-sm font-medium block mb-1">Conseiller</label>
            <select id="counselor_id" name="counselor_id" class="w-full px-3 py-2 rounded-lg border border-brand/15">
                <option value="">— Aucun —</option>
                <?php foreach ($counselors as $counselor): ?>
                <option value="<?= (int) $counselor['id'] ?>" <?= (int) ($project['counselor_id'] ?? 0) === (int) $counselor['id'] ? 'selected' : '' ?>>
                    <?= e(trim($counselor['first_name'] . ' ' . $counselor['last_name'])) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label for="note" class="text-sm font-medium block mb-1">Note (optionnel)</label>
            <input id="note" name="note" placeholder="Ex. Client demande un suivi rapproché…"
                   class="w-full px-3 py-2 rounded-lg border border-brand/15">
        </div>
        <button type="submit" class="px-5 py-2 rounded-lg bg-brand text-brand-foreground font-semibold text-sm hover:bg-brand-dark transition">Affecter</button>
    </form>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-brand/10 p-5 mt-6">
    <h2 class="font-bold text-lg mb-4">Workflow du dossier</h2>
    <?php if ($steps === []): ?>
        <p class="text-sm text-ink/60">Aucune étape définie.</p>
    <?php else: ?>
        <ol class="relative border-l border-brand/15 ml-2 space-y-6">
            <?php foreach ($steps as $step): ?>
            <?php
            $isCurrent = $currentStep !== null && (int) $currentStep['id'] === (int) $step['id'];
            $transitions = WorkflowService::ALLOWED_TRANSITIONS[$step['status']] ?? [];
            ?>
            <li class="pl-6 relative <?= $isCurrent ? 'bg-brand/5 -mx-5 px-5 py-3 rounded-xl' : '' ?>">
                <span class="absolute -left-[10px] top-0.5 w-4 h-4 rounded-full border-2
                    <?= $step['status'] === 'termine' ? 'bg-brand border-brand' : ($step['status'] === 'en_cours' ? 'bg-gold border-gold' : ($step['status'] === 'bloque' ? 'bg-red-500 border-red-500' : 'bg-white border-brand/25')) ?>"></span>

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-sm">
                            <?= $isCurrent ? '➤ ' : '' ?><?= e($step['name']) ?>
                            <span class="ml-2 text-xs px-2 py-0.5 rounded-full
                                <?= $step['status'] === 'termine' ? 'bg-emerald-50 text-emerald-brand' : ($step['status'] === 'en_cours' ? 'bg-gold/15 text-brand' : ($step['status'] === 'bloque' ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-ink/60')) ?>">
                                <?= e(WorkflowService::formatLabel($step['status'])) ?>
                            </span>
                        </p>
                        <?php if ($step['description']): ?>
                            <p class="text-xs text-ink/60 mt-1"><?= e($step['description']) ?></p>
                        <?php endif; ?>
                        <p class="text-[11px] text-ink/45 mt-1">
                            Position <?= (int) $step['position'] + 1 ?>
                            <?= $step['due_date'] ? ' · Échéance : ' . e(WorkflowService::formatDate($step['due_date'])) : '' ?>
                            <?= $step['started_at'] ? ' · Début : ' . e(date('d/m/Y', strtotime($step['started_at']))) : '' ?>
                            <?= $step['completed_at'] ? ' · Fin : ' . e(date('d/m/Y', strtotime($step['completed_at']))) : '' ?>
                        </p>
                    </div>

                    <?php if ($canSteps): ?>
                    <form method="POST" action="<?= e(route('admin.projects.steps.transition', ['publicId' => $project['public_id']])) ?>"
                          class="flex flex-wrap items-end gap-2 text-xs shrink-0">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="step_id" value="<?= e($step['public_id']) ?>">
                        <?php if ($transitions !== []): ?>
                        <div>
                            <label class="block text-ink/50 mb-0.5">Passer à</label>
                            <select name="to_status" class="px-2 py-1.5 rounded-lg border border-brand/15">
                                <?php foreach ($transitions as $t): ?>
                                <option value="<?= e($t) ?>"><?= e(WorkflowService::formatLabel($t)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="to_status" value="comment">
                        <?php endif; ?>
                        <div>
                            <label class="block text-ink/50 mb-0.5">Commentaire</label>
                            <input name="comment" placeholder="Optionnel"
                                   class="px-2 py-1.5 rounded-lg border border-brand/15 w-40">
                        </div>
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-brand text-brand-foreground hover:bg-brand-dark transition">
                            <?= $transitions !== [] ? 'Valider' : 'Commenter' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>

<div class="grid gap-6 lg:grid-cols-2 mt-6">
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-lg">Documents (<?= count($documents) ?>)</h2>
            <span class="text-xs text-ink/50">brouillon · final · archive</span>
        </div>

        <form method="POST" action="<?= e(route('admin.projects.documents.store', ['publicId' => $project['public_id']])) ?>"
              enctype="multipart/form-data" class="grid sm:grid-cols-2 gap-3 text-sm">
            <?= CSRF::field() ?>
            <input type="file" name="files[]" multiple required
                   class="sm:col-span-2 px-3 py-2 rounded-lg border border-brand/15 text-sm">
            <select name="category" class="px-3 py-2 rounded-lg border border-brand/15">
                <?php foreach (\App\Models\Document::CATEGORIES as $cat): ?>
                <option value="<?= e($cat) ?>"><?= e(ucfirst(str_replace('_', ' ', $cat))) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="visibility" class="px-3 py-2 rounded-lg border border-brand/15">
                <option value="private">Visible : équipe</option>
                <option value="client">Visible : client</option>
                <option value="admin">Visible : admins</option>
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Téléverser</button>
        </form>

        <?php if ($documents === []): ?>
            <p class="text-sm text-ink/60 mt-4">Aucun document. Téléversez vos premiers fichiers ci-dessus (fichiers ajoutés en « brouillon », non visibles par le client).</p>
        <?php else: ?>
        <ul class="divide-y divide-brand/5 mt-4 max-h-80 overflow-y-auto">
            <?php foreach ($documents as $document): ?>
            <li class="py-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium truncate"><?= e($document['original_name']) ?></p>
                    <p class="text-[11px] text-ink/50 mt-0.5">
                        <?= e(ucfirst(str_replace('_', ' ', $document['category']))) ?> · <?= number_format((float) $document['size'] / 1024, 0, '.', ' ') ?> Ko
                        · <?= e(date('d/m/Y H:i', strtotime($document['created_at']))) ?>
                    </p>
                    <p class="text-[11px] mt-1">
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold <?= $document['status'] === 'final' ? ($document['visibility'] === 'client' ? 'bg-emerald-50 text-emerald-brand' : 'bg-brand/10 text-brand') : 'bg-gray-100 text-ink/60' ?>">
                            <?= $document['status'] === 'brouillon' ? 'Brouillon' : ($document['status'] === 'final' ? 'Final · ' . (['client' => 'client', 'private' => 'équipe', 'admin' => 'admins'][$document['visibility']] ?? $document['visibility']) : 'Archivé') ?>
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="<?= e(route('admin.projects.documents.download', ['publicId' => $project['public_id'], 'documentPublicId' => $document['public_id']])) ?>"
                       class="text-xs text-brand hover:underline">Télécharger ↓</a>
                    <?php if ($canSteps): ?>
                        <?php if ($document['status'] !== 'final'): ?>
                        <form method="POST" action="<?= e(route('admin.projects.documents.status', ['publicId' => $project['public_id'], 'documentPublicId' => $document['public_id']])) ?>">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="status" value="final">
                            <button type="submit" class="text-xs text-emerald-brand hover:underline">Finaliser</button>
                        </form>
                        <?php endif; ?>
                        <?php if ($document['status'] === 'final'): ?>
                        <form method="POST" action="<?= e(route('admin.projects.documents.status', ['publicId' => $project['public_id'], 'documentPublicId' => $document['public_id']])) ?>">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="status" value="archive">
                            <button type="submit" class="text-xs text-ink/50 hover:underline">Archiver</button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-lg">Médias (<?= count($media) ?>)</h2>
            <span class="text-xs text-ink/50">photos · plans</span>
        </div>

        <form method="POST" action="<?= e(route('admin.projects.media.store', ['publicId' => $project['public_id']])) ?>"
              enctype="multipart/form-data" class="grid sm:grid-cols-2 gap-3 text-sm">
            <?= CSRF::field() ?>
            <input type="file" name="files[]" multiple accept="image/*" required
                   class="sm:col-span-2 px-3 py-2 rounded-lg border border-brand/15 text-sm">
            <label class="px-3 py-2 rounded-lg border border-brand/15 text-ink/60">
                Alt <input type="text" name="alt_text" placeholder="Description image" class="w-full outline-none text-ink">
            </label>
            <select name="visibility" class="px-3 py-2 rounded-lg border border-brand/15">
                <option value="private">Visible : équipe</option>
                <option value="client">Visible : client</option>
                <option value="admin">Visible : admins</option>
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg bg-brand text-brand-foreground font-semibold hover:bg-brand-dark transition">Téléverser</button>
        </form>

        <?php if ($media === []): ?>
            <p class="text-sm text-ink/60 mt-4">Aucune photo. Partagez des images du dossier (chantier, plans, maquettes…).</p>
        <?php else: ?>
        <ul class="divide-y divide-brand/5 mt-4 max-h-80 overflow-y-auto">
            <?php foreach ($media as $item): ?>
            <li class="py-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium truncate"><?= e($item['original_name']) ?></p>
                    <p class="text-[11px] text-ink/50 mt-0.5">
                        <?= number_format((float) $item['size'] / 1024, 0, '.', ' ') ?> Ko
                        · <?= e(date('d/m/Y H:i', strtotime($item['created_at']))) ?>
                    </p>
                    <p class="text-[11px] mt-1">
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold <?= $item['visibility'] === 'client' ? 'bg-emerald-50 text-emerald-brand' : 'bg-gray-100 text-ink/60' ?>">
                            <?= $item['visibility'] === 'client' ? 'Visible client' : ($item['visibility'] === 'private' ? 'Équipe' : 'Admins') ?>
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="<?= e(route('admin.projects.media.download', ['publicId' => $project['public_id'], 'mediaPublicId' => $item['public_id']])) ?>"
                       class="text-xs text-brand hover:underline">Télécharger ↓</a>
                    <?php if ($canSteps): ?>
                        <?php if ($item['visibility'] !== 'client'): ?>
                        <form method="POST" action="<?= e(route('admin.projects.media.visibility', ['publicId' => $project['public_id'], 'mediaPublicId' => $item['public_id']])) ?>">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="visibility" value="client">
                            <button type="submit" class="text-xs text-emerald-brand hover:underline">Publier client</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="<?= e(route('admin.projects.media.visibility', ['publicId' => $project['public_id'], 'mediaPublicId' => $item['public_id']])) ?>">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="visibility" value="private">
                            <button type="submit" class="text-xs text-ink/50 hover:underline">Retirer</button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2 mt-6">
    <?php if ($properties !== []): ?>
    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <h2 class="font-bold mb-3">Biens associés (<?= count($properties) ?>)</h2>
        <ul class="divide-y divide-brand/5">
            <?php foreach ($properties as $property): ?>
            <li class="py-3 flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium"><?= e($property['name']) ?></p>
                    <p class="text-xs text-ink/60"><?= e(ucfirst(str_replace('_', ' ', $property['type']))) ?>
                        <?= $property['locality'] ? ' · ' . e($property['locality']) : '' ?></p>
                </div>
                <div class="text-right shrink-0">
                    <?php if ($property['price'] !== null): ?>
                        <p class="text-sm font-bold text-brand"><?= format_number($property['price']) ?> <?= e($property['currency']) ?></p>
                    <?php endif; ?>
                    <p class="text-[11px] text-ink/50"><?= e(WorkflowService::formatLabel($property['status'])) ?></p>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-brand/10 p-5">
        <h2 class="font-bold mb-3">Activité récente</h2>
        <?php if ($history === []): ?>
            <p class="text-sm text-ink/60">Aucune activité enregistrée.</p>
        <?php else: ?>
            <ul class="divide-y divide-brand/5 max-h-72 overflow-y-auto">
                <?php foreach ($history as $entry): ?>
                <li class="py-2.5 text-sm">
                    <p class="font-medium">
                        <?php if ($entry['action'] === 'status'): ?>
                            Étape mise à jour :
                            <span class="text-gold"><?= e(WorkflowService::formatLabel($entry['from_status'] ?? '')) ?></span> →
                            <span class="text-emerald-brand"><?= e(WorkflowService::formatLabel($entry['to_status'] ?? '')) ?></span>
                        <?php else: ?>
                            Commentaire ajouté
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($entry['comment'])): ?>
                        <p class="text-xs text-ink/60 mt-0.5">"<?= e($entry['comment']) ?>"</p>
                    <?php endif; ?>
                    <p class="text-[11px] text-ink/45 mt-0.5">
                        <?= e(trim(($entry['first_name'] ?? '') . ' ' . ($entry['last_name'] ?? ''))) ?> · <?= e(date('d/m/Y H:i', strtotime($entry['created_at']))) ?>
                    </p>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php if ($canManage): ?>
<div class="flex flex-wrap items-center gap-3 mt-6">
    <?php foreach (['en_cours' => 'Remettre en cours', 'bloque' => 'Bloquer', 'termine' => 'Marquer terminé', 'archive' => 'Archiver'] as $status => $label): ?>
    <?php if ($project['status'] !== $status): ?>
        <form method="POST" action="<?= e(route('admin.projects.status', ['publicId' => $project['public_id']])) ?>">
            <?= CSRF::field() ?>
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <button type="submit"
                class="px-4 py-2 rounded-lg text-sm font-semibold transition
                <?= $status === 'en_cours' ? 'bg-emerald-100 text-emerald-brand hover:bg-emerald-50' : ($status === 'bloque' ? 'bg-red-100 text-red-700 hover:bg-red-50' : ($status === 'termine' ? 'bg-brand/10 text-brand hover:bg-brand/15' : 'bg-gray-100 text-ink/60 hover:bg-gray-50')) ?>">
                <?= e($label) ?>
            </button>
        </form>
    <?php endif; ?>
    <?php endforeach; ?>
    <a href="<?= e(route('admin.projects.edit', ['publicId' => $project['public_id']])) ?>" class="px-4 py-2 rounded-lg border border-brand/15 text-sm hover:bg-cream transition">Modifier le dossier</a>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>