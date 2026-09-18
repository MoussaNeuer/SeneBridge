# SeneBridge — Bloc 1

Plateforme de gestion de clients, biens et projets pour la diaspora sénégalaise.
Le **Bloc 1** pose les fondations : base de code structurée, base de données
complète et authentification robuste.

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
| `php public/index.php seed` | Rôles (5), permissions (31), mappings (89), admin |
| `php public/index.php route:list` | Routes enregistrées |

## Architecture

```
app/
  Controllers/Web/    Home, Auth, Dashboard (Bloc 1)
  Middlewares/        Auth, Guest, Csrf, EmailVerified, Role
  Models/             Model base + HasPublicId (ULID), User, Role, Permission
  Repositories/       UserRepository
  Services/           AuthService, TokenService
  Support/            App, Request, Response, Router, View, Validator, Config,
                      Database, Hasher, CSRF, RateLimiter, Mailer, Audit, Log, Str
  Validators/         AuthValidator (règles en français)
config/               app, database, security, mail, storage, payment
views/                layouts, public, auth, client, errors, emails
routes/               web.php (Bloc 1 : site public + authentification + dashboard)
database/             migrations/*.sql + schema.sql (référence)
tests/                PHPUnit (28 tests) — redondance non couverte par l'UI
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

## Suite (feuille de route)

- Espace client complet : projets, timeline des étapes, documents/médias,
  messagerie, factures/paiements (Phases 5–8)
- Back-office admin : gestion des rôles/permissions, utilisateurs (Phases 9+)
- SMTP de production, HTTPS/CSP renforcé, sauvegardes