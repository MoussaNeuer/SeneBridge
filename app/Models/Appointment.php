<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Rendez-vous entre un client et un conseiller.
 */
final class Appointment extends Model
{
    protected static string $table = 'appointments';

    protected static array $fillable = [
        'public_id', 'project_id', 'client_id', 'counselor_id', 'requested_date',
        'requested_time', 'motive', 'status', 'notes', 'created_by',
    ];

    public const STATUSES = ['demande', 'confirme', 'annule', 'termine'];
}