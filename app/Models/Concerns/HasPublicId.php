<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Symfony\Component\Uid\Ulid;

/**
 * Génère un identifiant public ULID (26 caractères base32, triable
 * chronologiquement, non énumérable). Chaque table "adressable" l'expose.
 */
trait HasPublicId
{
    protected static function newPublicId(): string
    {
        return (new Ulid())->toBase32();
    }
}