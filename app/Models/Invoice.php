<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Facture émise au client (statuts pilotés par les paiements validés).
 */
final class Invoice extends Model
{
    protected static string $table = 'invoices';

    protected static array $fillable = [
        'public_id', 'number', 'client_id', 'project_id', 'amount', 'paid_amount',
        'currency', 'issue_date', 'due_date', 'status', 'description', 'paid_at', 'created_by',
    ];

    public const STATUSES = ['brouillon', 'envoyee', 'partielle', 'payee', 'en_retard', 'annulee'];

    public static function findByNumber(string $number): ?array
    {
        return self::firstWhere(['number' => $number]);
    }
}