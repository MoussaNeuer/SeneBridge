# API REST SeneBridge — `/api/v1`

API authentifiée par **Bearer token**. Toutes les réponses utilisent une
enveloppe JSON standard :

```json
{
  "success": true,
  "data": { ... },
  "message": "OK",
  "errors": []
}
```

En cas d'erreur : `success: false`, `message` descriptif, `errors` (détails de
validation) et un code HTTP pertinent (`401`, `403`, `404`, `422`, `429`, `500`).

## Authentification

### `POST /api/v1/auth/login`

```json
{ "email": "client.demo@senebridge.sn", "password": "..." }
```

Réponse `200` :

```json
{
  "success": true,
  "data": {
    "token_type": "Bearer",
    "access_token": "sbt_...",
    "expires_at": "2026-10-18 12:00:00",
    "user": { "id": 5, "public_id": "...", "email": "...", "role": "client" }
  }
}
```

Le token brut n'est affiché qu'une fois. Toutes les autres requêtes utilisent
l'en-tête `Authorization: Bearer <token>`.

### `POST /api/v1/auth/logout`

Révoque le token courant. Réponse `200`.

### `GET /api/v1/me`

Profil de l'utilisateur authentifié.

## Dossiers

| Méthode | Route | Description |
| --- | --- | --- |
| GET | `/projects` | Liste (client : ses dossiers ; personnel : paginé, filtres `status`, `type`, `page`, `per_page`) |
| GET | `/projects/{publicId}` | Détail du dossier (+ client, conseiller, avancement) |
| GET | `/projects/{publicId}/steps` | Étapes du dossier |
| GET | `/projects/{publicId}/properties` | Biens rattachés |
| GET | `/projects/{publicId}/documents` | Documents (un client ne voit que `client`/`admin`) |
| GET | `/projects/{publicId}/messages` | Fil de discussion du dossier |
| POST | `/projects/{publicId}/messages` | Envoie un message (`body` requis) |

## Facturation

| Méthode | Route | Description |
| --- | --- | --- |
| GET | `/invoices` | Factures (client : les siennes ; personnel : paginé, filtre `status`) |
| GET | `/invoices/{publicId}` | Détail + `payments` associés |
| GET | `/payments` | Paiements (mêmes règles de portée) |
| GET | `/payments/{publicId}` | Détail d'un paiement |

## Rendez-vous

| Méthode | Route | Description |
| --- | --- | --- |
| GET | `/appointments` | Liste (client : les siens ; personnel : paginé, filtre `status`) |
| GET | `/appointments/{publicId}` | Détail |
| POST | `/appointments` | Crée une demande (`counselor_id`, `requested_date`, `requested_time`, `motive` requis ; `project_id`, `notes` optionnels). E-mail vérifié exigé |
| POST | `/appointments/{publicId}/cancel` | Annule un rendez-vous `demande` ou `confirme` |

## Notifications

| Méthode | Route | Description |
| --- | --- | --- |
| GET | `/notifications` | Liste paginée (`page`, `per_page`) |
| GET | `/notifications/unread-count` | Nombre de notifications non lues |
| POST | `/notifications/read-all` | Marque toutes les notifications comme lues |
| POST | `/notifications/{publicId}/read` | Marque une notification comme lue |

## Sécurité

- Tokens : préfixe `sbt_`, hash SHA-256 stocké, expiration 30 jours, révocation
  à la déconnexion et au changement de mot de passe.
- Rate limiting : connexion 5/15 min (IP + compte), requêtes 300/15 min par token.
- Portée : un client n'accède qu'à ses propres ressources ; les ressources d'un
  autre client renvoient 404.
- Détails : voir [`docs/SECURITY.md`](SECURITY.md).

## Exemple (curl)

```bash
# Connexion
TOKEN=$(curl -s -X POST http://localhost/SeneBridge/public/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"client.demo@senebridge.sn","password":"..."}' \
  | php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["data"]["access_token"];')

# Liste des dossiers du client
curl -s http://localhost/SeneBridge/public/api/v1/projects \
  -H "Authorization: Bearer $TOKEN"
```