# SeneBridge — Blocs 1 à 4

Plateforme de gestion de clients, biens et projets pour la diaspora sénégalaise.
Le **Bloc 1** pose les fondations : base de code structurée, base de données
complète et authentification robuste. Le **Bloc 2** ajoute le site public
(fonctionnel), l'espace client et le back-office complet avec RBAC et suivi
de projets/étapes. Le **Bloc 3** ajoute documents & médias par projet,
factures & paiements, messagerie par projet et rendez-vous (demande client /
confirmation conseiller). Le **Bloc 4** ajoute l'API REST `/api/v1` (tokens
Bearer), le dashboard de pilotage financier du back-office et le durcissement
de sécurité (sessions, API, en-têtes HTTP).

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
| `php public/index.php seed` | Rôles (5), permissions (36), affectations (87), admin |
| `php public/index.php route:list` | Routes enregistrées |

## Architecture

```
app/
  Controllers/Web/    Home, Auth, Dashboard, Profile, Projects, Notifications,
                      ClientProjects, ClientInvoices, ClientMessages,
                      ClientAppointments, Start, Contact, ...
  Controllers/Api/    Auth, Project, Invoice, Payment, Appointment, Message,
                      Notification, ProjectStep, ProjectDocument (API /api/v1)
  Controllers/Admin/  Dashboard, Projects, Clients, Counselors, Properties,
                      Requests, Contacts, Articles, Invoices, Payments,
                      Messages, Appointments (portées RBAC)
  Middlewares/        Auth, Guest, Csrf, EmailVerified, Role, Permission,
                      AdminArea (garde du back-office /admin/*), Api (alias api)
  Models/             Model base + HasPublicId (ULID), User, Role, Permission,
                      Project, ProjectStep, Document, Media, Invoice, Payment,
                      Conversation, ConversationMessage, Appointment, Article,
                      ApiToken, ...
  Policies/           ProjectPolicy, ClientPolicy, PropertyPolicy, ...
  Repositories/       UserRepository, ProjectRepository, ConversationRepository,
                      InvoiceRepository, PaymentRepository, DocumentRepository, ...
  Services/           AuthService, TokenService, ProjectService, WorkflowService,
                      DocumentService, InvoiceService, PaymentService,
                      AppointmentService, Notifier, AuditService, ApiTokenService
  Support/            App, Request, Response, Router, View, Validator, Config,
                      Database, Hasher, CSRF, RateLimiter, Mailer, Audit, Log, Str
  Validators/         AuthValidator, ProfileValidator, ProjectValidator,
                      DocumentValidator, InvoiceValidator, PaymentValidator,
                      MessageValidator, AppointmentValidator, ...
config/               app, database, security, mail, storage, payment
views/                layouts, public, auth, client, admin, errors, emails
routes/               web.php (public + auth) + admin.php (garde AdminArea) + api.php (/api/v1)
database/             migrations/*.sql + schema.sql (référence)
tests/                PHPUnit (41 tests) — validation via probes HTTP en plus
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
- **Headless REST API `/api/v1`** : authentification par **tokens Bearer**
  (`sbt_...`, seul le hash SHA-256 est stocké, TTL 30 j réglable dans
  `config/security.php`), rate limiting par IP et par compte (login
  5/15 min, global 300/15 min → 429 + `Retry-After`), limites de propriété
  anti-IDOR (dossier/facture d'un autre client → 404), pagination standard,
  erreurs JSON cohérentes. `POST /api/v1/auth/login`, `GET /me`,
  `projects`, `invoices`, `payments`, `appointments`, `messages`,
  `notifications` (+ `project steps/documents`). Contrats détaillés dans
  `docs/api.md` ; tous les endpoints passent le middleware `api`.
- **Changement de mot de passe** : invalide les autres sessions (`session_version`)
  et révoque les tokens API existants.
- **Headers de sécurité** : CSP (`default-src 'self'` + Google Fonts), nosniff,
  `Referrer-Policy`, `Permissions-Policy`, `X-Frame-Options: DENY`, HSTS en production.
- **Sessions** : cookie HttpOnly + SameSite=Lax, `use_strict_mode=1`, regénération d'ID à la connexion.
- **Emails** : driver `log` (fichier `storage/logs/mail.log`) ; SMTP prêt (Phase production).
- **Pile de buffers** : `View::render` est consciente de `output_buffering=On` (WAMP).
- **Zone back-office** : toutes les routes `/admin/*` passent par le middleware
  `AdminArea` (alias `staff`) qui refuse les rôles non-personnel (`client`) même
  si des permissions résiduelles sont seedées. Le rôle `client` ne détient
  aucune permission du back-office (moindre privilège) ; l'espace client est
  protégé par authentification + vérifications de propriété (anti-IDOR).

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
  changements d'étape/statut, lectures complètes possibles, lecture dans l'espace client
- **Identifiants exposés** : `public_id` ULID sur les projets *et* les étapes
  (migration `0010`) ; l'API interne ne joint que sur les `id` numériques

## Bloc 3 — documents, factures, messagerie, rendez-vous

- **Documents & médias par projet** : upload back-office (docs et médias) avec
  règles de validation (type MIME, taille), visibilité `client`/`private`/`admin`,
  workflow doc (`brouillon → final → archive`). Le client consulte (lecture seule)
  les documents `final` visibles et télécharge ses médias ; l'équipe garde le pilotage.
- **Factures & paiements** : facturation (création client+projet, montant, devise,
  `brouillon → envoyee → partielle → payee / en_retard`), statuts recalculés
  automatiquement à partir des paiements (serait système de tolérance), envoi
  au client, paiements enregistrés (`en_cours`) puis validés/rejetés par l'équipe
  (le reçu peut être joint), notifications client à la validation.
- **Messagerie par projet** : une conversation par dossier, liste de threads,
  envoi de messages admin/counselor ↔ client avec `conversations.status`
  (`ouverte`/`archive`), badge de non-lus.
- **Rendez-vous** : demande client (choix conseiller, motif, date) →
  confirmation/refus par le conseiller ; statuts
  `demande → confirme / refuse → realise / annule`. Le champ conseiller est requis.
- **Notifications** : bouton « Tout marquer comme lu ».
- **Tableau de bord back-office** : 2e rangée de stats (factures à recouvrer,
  paiements à valider, RV à confirmer, conversations non lues).

## Bloc 4 — API REST, dashboard financier, durcissement

- **API REST `/api/v1`** : token Bearer (`sbt_...`), TTL 30 jours, hash SHA-256
  stocké, révocation à la déconnexion, rate limiting login + global, alias
  `api` middleware et groupe de routes. Endpoints : auth (login/logout/me),
  projets (liste/détail + étapes + documents clients), factures (avec statuts
  de paiement), paiements, rendez-vous (création/annulation), messagerie,
  notifications. Erreurs JSON (401/403/404/422/429/500), pagination.
  Documentation : `docs/api.md`.
- **Dashboard back-office** : rangée de pilotage financier — CA total encaissé,
  encours à recouvrer, taux de recouvrement, revenu mensuel CA (ventes) vs
  encaissé (paiements validés) sur 12 mois (graphique barres), répartition des
  dossiers par statut (barres horizontales), derniers paiements validés, RV à venir.
- **Durcissement** : invalidation de session au changement de mot de passe et
  révocation des tokens API ; en-têtes HTTP renforcés (HSTS hors debug) ;
  exceptions du noyau servies en JSON sur `/api/*` ; `docs/SECURITY.md`.
- **État par requête sous serveur intégré** : `App::resetRequestState()` +
  `Router::reset()` rejouent l'état (caches utilisateur/requête/token, routes)
  à chaque requête (statiques PHP persistantes).

## Comptes de démonstration (dev uniquement)

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Client | client.demo@senebridge.sn | NewClient@2026! |
| Gestionnaire | manager.demo@senebridge.sn | Manager@2026! |
| Conseiller | counselor.demo@senebridge.sn | Counselor@2026! |
| Admin | admin@senebridge.sn | Admin@2026! |

(seedés via script de dev jetable, hors dépôt — à refresher avant régression)

## Chef de validation (Bloc 3)

- `php vendor/bin/phpunit` : 31 tests / 54 assertions OK
- Lint : tous les fichiers `*.php` sans erreur (`php -l`)
- Probes HTTP sur serveur dev (`127.0.0.1:8088`) : guests redirigés vers
  `/login`, pages client 200, création+annulation de rendez-vous,
  client bloqué sur `/admin/*` (302), flux complet back-office facture →
  envoi → paiement → validation (200/302)
- `npm run build` (Tailwind v4) OK

## Chef de validation (Bloc 4)

- `php vendor/bin/phpunit` : **41 tests / 126 assertions OK** (ApiTokenService,
  Request/bearer, Response JSON, durcissement)
- Lint : tous les fichiers `*.php` sans erreur (`php -l`)
- `npm run build` (Tailwind v4) OK
- Probes HTTP sur serveur dev (`127.0.0.1:8088`) : en-têtes sécurité, dashboard
  admin (marqueurs financiers), **API complète** — login 200 → token, `/me`
  (rôle `client`), CRUD lecture (projets/factures/notifications/étapes/docs),
  401 sans/avec mauvais token, 404 JSON route inconnue, login mauvais mot de
  passe 401, création rendez-vous, messages index/envoi, **anti-IDOR** (dossier
  et facture d'un autre client → 404), pagination factures (`last_page ≥ 1`),
  admin lit facture + paiements + documents projet. HSTS absent en mode debug
  (attendu)

## Suite (feuille de route)

- Paiement en ligne (intégration Wave/Orange Money) et reçus PDF
- Gestion des rôles/permissions dans l'UI (Phases 9+)
- SMTP de production, HTTPS/CSP renforcé, sauvegardes
- Limite d'upload dev : PHP WAMP (`upload_max_filesize` ≈ 2M) à augmenter en production