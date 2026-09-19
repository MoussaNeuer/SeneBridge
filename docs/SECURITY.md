# Sécurité — SeneBridge

Ce document décrit les mesures de sécurité activées/modifiées dans le Bloc 4
(Phase 16 — durcissement). Il sert de référence pour l'audit et l'exploitation.

## 1. Authentification et sessions

- **Mots de passe** : Argon2id (`PASSWORD_ARGON2ID`) avec paramètres configurables
  dans `config/security.php` (`password.memory_cost`, `time_cost`, `threads`).
- **Sessions** : cookie `HttpOnly`, `SameSite=Lax`, nom dédié
  (`senebridge_session`), régénération de l'ID à la connexion
  (`session_regenerate_id(true)`), `session.use_strict_mode = 1`,
  expiration 120 minutes (configurable).
- **Invalidation de sessions après changement de mot de passe** :
  la colonne `users.session_version` est incrémentée à chaque changement de mot
  de passe (`UserRepository::updatePassword`). Toute session existante portant une
  `session_version` antérieure est invalidée à la prochaine requête
  (`App::user()`). La session qui effectue le changement reste valide
  (resynchronisation dans `ProfileController::password`).
- **Comptes suspendus** : les utilisateurs `status = 'suspended'` sont rejetés à
  la connexion et à chaque requête (web et API).

## 2. API REST (`/api/v1`)

- **Tokens Bearer** : générés en 64 caractères hexadécimaux (32 octets aléatoires),
  préfixés `sbt_`. Seul le **hash SHA-256** est stocké en base (`api_tokens.token_hash`) ;
  le token brut n'est restitué qu'à la création.
- **Expiration** : `expires_at` selon `security.api.token_ttl_days` (30 jours par
  défaut). Un token expiré ou révoqué est refusé.
- **Révocation** : à la déconnexion (token courant), au changement de mot de passe
  (tous les tokens de l'utilisateur), ou manuellement dans le back-office.
- **Rate limiting** :
  - `POST /auth/login` : 5 tentatives / 15 min par IP **et** par compte
    (`security.api.login`) ;
  - requêtes authentifiées : 300 / 15 min par token (`security.api.general`).
  - Toutes les réponses de limitation renvoient `429` avec un en-tête
    `Retry-After`.
- **Propriété des ressources** : un client n'accède qu'à ses propres dossiers,
  factures, paiements, rendez-vous, notifications ; toute ressource d'un autre
  client renvoie 404 (pas de fuite d'existence). Le personnel (admin, manager,
  conseiller, comptable) accède à l'ensemble.
- **Erreurs** : toutes les erreurs sous `/api/*` sont au format JSON
  (`{ success, data, message, errors }`), y compris les 404 et les erreurs 500
  inattendues (gérées par l'exception handler).

## 3. Journaux et audit

- `audit_logs` trace les actions sensibles (connexions, création/révocation de
  tokens API, changements de mot de passe, annulation de rendez-vous, etc.),
  sans jamais stocker de secret. L'écriture d'audit ne fait jamais échouer le flux
  métier.
- `rate_limits` : compteurs persistant, purgés par
  `php public/index.php rate-limiter:purge` (ou le script
  `C:\Users\MOUSSA~1\AppData\Local\Temp\opencode\purge_rate.php`).

## 4. En-têtes HTTP

Appliqués par `Response::applySecurityHeaders()` sur toutes les réponses :

| En-tête | Valeur |
| --- | --- |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` |
| `X-Frame-Options` | `DENY` (HTML) |
| `Content-Security-Policy` | règles strictes (HTML) — cf. `Response.php` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` — **uniquement hors debug** |

## 5. Conseil d'exploitation pour la mise en production

1. Passer `APP_ENV=production` et `APP_DEBUG=false` (active HSTS, masque les stack
   traces). Rappel : ne jamais activer HSTS en HTTP pur sur le domaine.
2. Renforcer `security.session.cookie_secure = true` (HTTPS).
3. Chiffrer le trafic (TLS) côté serveur web.
4. Configurer une rotation longue des tokens (`API_TOKEN_TTL_DAYS`) selon la
   politique interne.
5. Surveiller `audit_logs` pour `auth.login.too_many`, `api.auth.login_failed`,
   `api_tokens.*`.
6. Les secrets (`.env`) ne sont jamais commités (`.gitignore`). Vérifier
   régulièrement.