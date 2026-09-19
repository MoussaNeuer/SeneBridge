<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Services\SettingsService;
use App\Support\App;
use App\Support\Audit;
use App\Support\Response;

/**
 * Hub « Paramètres » du back-office.
 * Affiche les réglages groupés par section et enregistre les modifications
 * (CSRF + journal d'audit à chaque enregistrement).
 */
final class SettingsController extends Controller
{
    public function index(): Response
    {
        return Response::view('admin/settings/index', [
            'user' => App::user(),
            'sections' => SettingsService::rowsets(),
            'groups' => SettingsService::rowsets(),
        ]);
    }

    /**
     * Enregistre les réglages validés hydratés par le formulaire du hub.
     */
    public function update(): Response
    {
        $keys = array_keys(SettingsService::rows());
        $updated = 0;

        foreach ($keys as $key) {
            $raw = (string) ($this->request->post($key) ?? '');

            if ($raw === '') {
                continue;
            }

            $row = SettingsService::rows()[$key] ?? null;

            if ($row === null) {
                continue;
            }

            $type = (string) ($row['type'] ?? 'string');

            SettingsService::set($key, $raw, $type);
            $updated++;
        }

        if ($updated > 0) {
            Audit::log('settings.updated', 'settings', 0, [], [
                'count' => $updated,
                'by' => (int) (App::user()['id'] ?? 0),
            ]);
            App::flash('success', sprintf('Les paramètres ont été enregistrés (%d réglage(s)).', $updated));
        } else {
            App::flash('info', 'Aucun réglage à enregistrer.');
        }

        return Response::redirect(route('admin.settings'));
    }
}
