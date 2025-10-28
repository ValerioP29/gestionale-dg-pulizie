<?php

namespace App\Services;

use App\Models\DgSiteAssignment;
use App\Models\User;
use Carbon\Carbon;

class SiteResolverService
{
    /**
     * Restituisce l'ID del cantiere effettivo per una certa data e utente.
     */
    public static function resolveFor(User $user, ?int $punchSiteId = null, ?Carbon $date = null): ?int
    {
        $date = $date ?? now();

        // 1. Se la timbratura ha site_id manuale, vince subito
        if ($punchSiteId) {
            return $punchSiteId;
        }

        // 2. Controlla se esiste un’assegnazione temporanea valida
        $assignment = DgSiteAssignment::query()
            ->where('user_id', $user->id)
            ->whereDate('assigned_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('assigned_to')
                  ->orWhereDate('assigned_to', '>=', $date);
            })
            ->orderByDesc('assigned_from')
            ->first();

        if ($assignment) {
            return $assignment->site_id;
        }

        // 3. Fallback: cantiere principale dell’utente
        return $user->main_site_id;
    }
}
