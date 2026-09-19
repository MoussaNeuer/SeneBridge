<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Support\Gate;
use App\Support\Response;

/**
 * Radiote de l'API REST : enveloppe JSON normalisée + contrôle d'accès.
 *
 * Enveloppe : { success, data, message, errors }
 */
abstract class ApiController extends Controller
{
    protected const STAFF_ROLES = ['admin', 'manager', 'counselor', 'accounting'];

    protected function ok(mixed $data = null, string $message = 'OK', int $status = 200): Response
    {
        return Response::json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => [],
        ], $status);
    }

    protected function created(mixed $data = null, string $message = 'Ressource créée.'): Response
    {
        return $this->ok($data, $message, 201);
    }

    protected function noContent(): Response
    {
        return Response::text('', 204);
    }

    protected function fail(string $message, int $status = 422, array $errors = []): Response
    {
        return Response::json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    protected function notFound(string $message = 'Ressource introuvable.'): Response
    {
        return $this->fail($message, 404);
    }

    protected function forbidden(string $message = 'Accès non autorisé.'): Response
    {
        return $this->fail($message, 403);
    }

    /**
     * Résultat paginé au format habituel.
     *
     * @param array{items: array<int, array<string, mixed>>, total: int, per_page: int, page: int, last_page: int} $page
     */
    protected function page(array $page, string $message = 'OK'): Response
    {
        return $this->ok([
            'items' => $page['items'],
            'total' => $page['total'],
            'per_page' => $page['per_page'],
            'page' => $page['page'],
            'last_page' => $page['last_page'],
        ], $message);
    }

    /**
     * Pagination demandée par l'appelant (page, per_page).
     *
     * @return array{page:int, per_page:int}
     */
    protected function paging(): array
    {
        $page = max(1, (int) $this->request->query('page', 1));
        $perPage = max(1, min(100, (int) $this->request->query('per_page', 20)));

        return ['page' => $page, 'per_page' => $perPage];
    }

    protected function user(): ?array
    {
        return $this->authUser;
    }

    protected function isStaff(): bool
    {
        return Gate::isRole($this->authUser, ...self::STAFF_ROLES);
    }

    protected function isClient(): bool
    {
        return Gate::isRole($this->authUser, 'client');
    }

    /**
     * Propriété de la ressource : client propriétaire ou personnel.
     * La façade d'accès d'une ressource appartient au client propriétaire.
     */
    protected function owns(?int $ownerClientId): bool
    {
        return $this->isStaff()
            || ($ownerClientId !== null && (int) $this->id() === $ownerClientId);
    }

    protected function id(): int
    {
        return (int) ($this->authUser['id'] ?? 0);
    }

    /**
     * Paysable « être vérifié » : l'e-mail vérifié est exigé pour les actions
     * sensibles (création, modification).
     */
    protected function emailVerified(): bool
    {
        return isset($this->authUser['email_verified_at']) && $this->authUser['email_verified_at'] !== null;
    }

    /**
     * Données utilisateur exposées par l'API (jamais de secret).
     */
    protected function userPayload(): array
    {
        return [
            'id' => $this->id(),
            'public_id' => $this->authUser['public_id'] ?? null,
            'first_name' => $this->authUser['first_name'] ?? '',
            'last_name' => $this->authUser['last_name'] ?? '',
            'email' => $this->authUser['email'] ?? '',
            'phone' => $this->authUser['phone'] ?? null,
            'role' => Gate::roleName($this->authUser),
            'email_verified' => $this->emailVerified(),
            'language' => $this->authUser['language'] ?? 'fr',
            'last_login_at' => $this->authUser['last_login_at'] ?? null,
            'created_at' => $this->authUser['created_at'] ?? null,
        ];
    }
}