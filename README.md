# SeneBridge — Blocs 1 & 2

Plateforme de gestion de clients, biens et projets pour la diaspora sénégalaise.
Le **Bloc 1** pose les fondations : base de code structurée, base de données
complète et authentification robuste. Le **Bloc 2** ajoute le site public
(fonctionnel), l'espace client et le back-office complet avec RBAC et suivi
de projets/étapes.

## Stack

- PHP **8.4** (WAMP) — pas de framework : framework maison minimaliste (`app/`)
- MySQL **8.4** (port 3306) — schéma 25 tables, FKs InnoDB, charset `utf8mb4`
- Tailwind **v4** (`npm run build` → `public/assets/css/app.css`)
- Composer : `monolog/monolog`, `symfony/uid` (ULID), `phpunit/phpunit`

## Mise en route

```bash
# 1. Créer la base (déjà fait en local)
mysql -u root -e "CREATE DATABASE senebridge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 2. Environnement
copy .env.example .env        # puis renseigner DB_*, APP_KEY (php public/index.php key:generate)

# 3. Dépendances + migration + seed
composer dump-autoload --optimize
php public/index.php migrate
php public/index.php seed     # crée rôles/permissions + admin@senebridge.sn (mot de passe affiché)

# 4. CSS (si modification des vues)
npm run build

# 5. Tests
php vendor/bin/phpunit

# 6. Serveur de dev (optionnel, WAMP fonctionne aussi sous /SeneBridge/public/)
php -S 127.0.0.1:8088 -t public devserver.php
```

## Commandes CLI

| Commande | Rôle |
|---|---|
| `php public/index.php key:generate` | Génère `APP_KEY` |
| `php public/index.php migrate` | Applique `database/migrations/*.sql` |
| `php public/index.php migrate:status` | Liste des migrations appliquées |
| `php public/index.php seed` | Rôles (5), permissions (36), affectations (98), admin |
| `php public/index.php route:list` | Routes enregistrées |

## Architecture

```
app/
  Controllers/Web/    Home, Auth, Dashboard, Profile, Projects, Notifications
                      (espace client), et pages publiques (Contact, Start, ...)
  Controllers/Admin/  Dashboard, Projects, Clients, Counselors, Properties,
                      Requests, Contacts, Articles (portées RBAC)
  Middlewares/        Auth, Guest, Csrf, EmailVerified, Role, Permission
  Models/             Model base + HasPublicId (ULID), User, Role, Permission,
                      Project, ProjectStep, Article, ...
  Policies/           ProjectPolicy, ClientPolicy, PropertyPolicy, ...
  Repositories/       UserRepository, ProjectRepository, ...
  Services/           AuthService, TokenService, ProjectService, WorkflowService,
                      Notifier, AuditService
  Support/            App, Request, Response, Router, View, Validator, Config,
                      Database, Hasher, CSRF, RateLimiter, Mailer, Audit, Log, Str
  Validators/         AuthValidator, ProfileValidator, ProjectValidator, ...
config/               app, database, security, mail, storage, payment
views/                layouts, public, auth, client, admin, errors, emails
routes/               web.php (public + auth) + admin.php (back-office RBAC)
database/             migrations/*.sql + schema.sql (référence)
tests/                PHPUnit (28 tests) — validation via probes HTTP en plus
```

## Décisions clés

- **Identifiants** : `id BIGINT UNSIGNED AUTO_INCREMENT` interne pour les
  jointures + colonne `public_id CHAR(26)` **ULID** (symfony/uid) exposée sur
  toutes les tables adressables. Jamais de jointure sur l'ULID.
- **Mots de passe** : Argon2id (`password_hash`), re-hash automatique à la connexion.
- **CSRF** : jeton par session, vérifié sur POST/PUT/PATCH/DELETE.
- **Rate limiting** : par IP et par compte (registrations, login, password reset),
  stocké en base (`rate_limits`), clés en `login:<email>`.
- **Tokens temporaires** : seul le hash SHA-256 est stocké ; usage unique, expiration.
- **Anti-énumération** : réponse identique que le compte existe ou non sur le password reset.
- **Headers de sécurité** : CSP (`default-src 'self'` + Google Fonts), nosniff,
  `Referrer-Policy`, `Permissions-Policy`, `X-Frame-Options: DENY`.
- **Sessions** : cookie HttpOnly + SameSite=Lax, `use_strict_mode=1`, regénération d'ID à la connexion.
- **Emails** : driver `log` (fichier `storage/logs/mail.log`) ; SMTP prêt (Phase production).
- **Pile de buffers** : `View::render` est consciente de `output_buffering=On` (WAMP).

## Pages / flux fonctionnels (Bloc 1)

- Accueil public (hero, services, à propos, contact)
- Inscription → e-mail de vérification → vérification → connexion
- Connexion / déconnexion
- Mot de passe oublié → e-mail → réinitialisation
- Tableau de bord client (placeholder, modules réels en Phase 5+)
- Pages d'erreur 404 / 403 / 500

## Pages / flux fonctionnels (Bloc 2)

- **Site public** : accueil, services, à propos, actualités (articles conclus),
  contact (soumission en base), formulaire « Démarrer un projet » → demande en base
- **Espace client** : dashboard, liste de projets, détail projet (timeline des
  étapes, statut, référence), profil (mise à jour + changement de mot de passe),
  notifications (liste + lecture)
- **RBAC** (`role_permissions`, middleware `Permission`) : `admin.access` porte
  d'entrée du back-office ; granularité par ressource (ex. `projects.create` ≠
  `projects.view`). Le conseiller consulte les projets et fait évoluer les étapes
  de ses dossiers sans pouvoir créer de projet.
- **Back-office** : dashboard, projets (vue liste + détail, workflow d'étapes,
  changement de statut projet, création/édition avec référence `SEN-xxxx-xxxx`),
  clients (fiche + création de projet depuis la fiche), conseillers, biens,
  demandes (traitement + notification client), contacts, articles (publié/brouillon)
- **Workflow d'étapes** : transitions autorisées par statut
  (`brouillon → en_cours → bloque/reprendre → termine`), commentaires, journal
  d'historisation + notifications vers le client
- **Notifications** : table `notifications`, envoi à la création de projet, aux
  changements d'étape/statut, lecture dans l'espace client
- **Identifiants exposés** : `public_id` ULID sur les projets *et* les étapes
  (migration `0010`) ; l'API interne ne joint que sur les `id` numériques

## Comptes de démonstration (dev uniquement)

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Client | client.demo@senebridge.sn | NewClient@2026! |
| Gestionnaire | manager.demo@senebridge.sn | Manager@2026! |
| Conseiller | counselor.demo@senebridge.sn | Counselor@2026! |
| Admin | admin@senebridge.sn | Admin@2026! |

(seedés via script de dev jetable, hors dépôt — à refresher avant régression)

## Chef de validation (Bloc 2)

- `php vendor/bin/phpunit` : 28 tests / 47 assertions OK
- Lint : tous les fichiers `*.php` sans erreur (`php -l`)
- Probes HTTP sur serveur dev (`127.0.0.1:8088`) : matrice publique + client +
  conseiller + gestionnaire + admin verte (voir log de session)
- `npm run build` (Tailwind v4) OK

## Suite (feuille de route)

- Espace client : documents/médias, messagerie, factures/paiements (Phases 5–8)
- Back-office : tableaux supplémentaires (documents, factures, paiements),
  gestion des rôles/permissions dans l'UI (Phases 9+)
- SMTP de production, HTTPS/CSP renforcé, sauvegardes