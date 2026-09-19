# SeneBridge — Déploiement (guide de mise en production)

Check-list de préproduction après les travaux des Blocs 1 à 5.

## 1. Variables d'environnement (`.env`)

| Variable | Valeur conseillée | Notes |
|---|---|---|
| `APP_ENV` | `production` | Active `upgrade-insecure-requests`, COOP/CORP, HSTS |
| `APP_DEBUG` | `false` | Désactive l'exposition des erreurs |
| `APP_URL` | `https://votre-domaine.sn/public` (ou sous-domaine) | Base des URLs générées |
| `APP_TRUST_PROXY` | `true` si derrière un reverse-proxy TLS (nginx/Caddy/Apache+mod_proxy) | Fie `Request::secure()` à `X-Forwarded-Proto`. Toujours garder à `false` si l'application reçoit du trafic HTTP direct. |
| `APP_KEY` | 64 caractères hexa | `php public/index.php key:generate` |
| `UPLOAD_MAX_SIZE` | ≤ `upload_max_filesize` PHP | Doit rester inférieur à `upload_max_filesize` ET `post_max_size` |
| `MEDIA_MAX_SIZE` | 52428800 (50 Mo) | Fichiers médias |
| `MAIL_MAILER` | `smtp` | — |
| `MAIL_HOST/PORT/USERNAME/PASSWORD/ENCRYPTION` | cf. fournisseur | `tls` (STARTTLS, port 587) ou `ssl` (465) |
| `MYSQLDUMP_BIN` | `mysqldump` ou chemin absolu (WAMP : `C:\wamp64\bin\mysql\mysql8.4\bin\mysqldump.exe`) | Auto-détecté pour les installs WAMP standard |
| `BACKUP_KEEP` | 7 | Rotation des dumps dans `storage/backups/database/` |

## 2. Vérifications automatiques

```bash
php public/index.php doctor
```

Contrôle : PHP score, extensions (`pdo_mysql`, `mbstring`, `dom`, `xml`,
`json`, `openssl`, `fileinfo`), limites d'upload PHP vs `UPLOAD_MAX_SIZE`,
`APP_KEY`, `APP_DEBUG`/proxy, répertoires inscriptibles (`storage/logs`,
`storage/private`), connexion MySQL. Arrêt au code 1 sur erreur fatale.

## 3. Limites d'upload (PHP/Apache)

Le paramétrage local WAMP historique (`upload_max_filesize` ≈ 2 Mo) doit être
relevé en production :

```ini
; php.ini
upload_max_filesize = 10M
post_max_size        = 12M
memory_limit         = 256M
file_uploads         = On
```

`upload_max_filesize` **doit rester ≥ `UPLOAD_MAX_SIZE`** (sinon les uploads
échouent côté PHP avant la validation applicative, cf. `doctor`).

## 4. SMTP

Le driver `log` écrit dans `storage/logs/mail.log` (développement). En
production :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.votrefournisseur.sn
MAIL_PORT=587
MAIL_USERNAME=no-reply@votre-domaine.sn
MAIL_PASSWORD=*****
MAIL_ENCRYPTION=tls
MAIL_AUTH=true
MAIL_TIMEOUT=15
MAIL_FROM_ADDRESS=no-reply@votre-domaine.sn
MAIL_FROM_NAME=SeneBridge
```

Le driver refuse proprement (journal `critical`/`error`) si les identifiants
sont absents : aucun courriel n'est perdu silencieusement.

## 5. Sauvegardes

Une seule commande, à planifier (cron / tâche planifiée) :

```bash
php public/index.php backup
```

- Dump MySQL : `storage/backups/database/senebridge-<horodatage>.sql`
  (`mysqldump --single-transaction`, mot de passe via la variable
  d'environnement `MYSQL_PWD`, jamais dans la ligne de commande).
- Fichiers privés : `storage/backups/files/<horodatage>/` (copie de
  `storage/private`).
- Rétention : conserve les `BACKUP_KEEP` dumps les plus récents, supprime les
  anciens.

Exemple de planification (cron) :

```
0 2 * * *  cd /var/www/SeneBridge && /usr/bin/php public/index.php backup >> storage/logs/backup.log 2>&1
```

Copiez les dumps hors de la machine (offsite) ; chiffrez-les si les données
sont sensibles.

## 6. HTTPS

- Reverse-proxy TLS devant Apache/`public/` (nginx, Caddy, ou Apache avec
  `mod_ssl` + HSTS). L'application sert du HTTPS et, en `production`,
  `upgrade-insecure-requests` dans le CSP force les ressources mixtes en
  HTTPS.
- `APP_TRUST_PROXY=true` uniquement si le proxy est bien le seul à pouvoir
  poser `X-Forwarded-Proto` (réseau de confiance), sinon l'en-tête peut être
  falsifié par le client.
- Le strict mode ressort dans `doctor` si `APP_ENV=production` sans proxy.

## 7. Index de données / journaux

- Le `backup` journalise dans `storage/logs/` (Monolog) ; l'audit applicatif
  (`audit_logs`) est tracé en base.
- Surveillance minimale : espace disque (`storage/backups`, `storage/logs`),
  erreurs 5xx, échecs SMTP (recherche « Échec de l'envoi SMTP » dans les logs).

## 8. Rappel sécurité

Consulter `docs/SECURITY.md` : secrets hors dépôt, `.env` en
dehors de la zone servie, cookies `HttpOnly`/`SameSite=Lax`, tokens API
stockés en hash, CSP stricte (aucun `<script>` inline), rate limiting actif.