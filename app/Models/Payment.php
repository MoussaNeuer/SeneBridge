<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Paiement (Wave Business, virement, espèces) rattaché à une facture et/ou un dossier.
 */
final class Payment extends Model
{
    protected static string $table = 'payments';

    protected static array $fillable = [
        'public_id', 'invoice_id', 'project_id', 'client_id', 'amount', 'currency',
        'payment_date', 'method', 'reference', 'status', 'receipt_internal_name', 'notes',
        'recorded_by', 'validated_at',
    ];

    public const METHOD_LABELS = [
        'wave_business' => 'Wave Business',
        'bank_transfer' => 'Virement bancaire',
        'cash' => 'Espèces',
        'other' => 'Autre',
    ];

    public const STATUSES = ['en_cours', 'valide', 'rejete', 'rembourse'];
}