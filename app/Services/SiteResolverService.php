<?php

namespace App\Services;

use App\Models\DgSite;
use App\Models\DgSiteAssignment;
use App\Models\User;
use Carbon\Carbon;

class SiteResolverService
{
    /**
     * Restituisce l'ID del cantiere effettivo per una certa data e utente.
     *
     * La risoluzione avviene dando priorità al cantiere passato manualmente,
     * poi all'assegnazione attiva nella data specificata e infine al cantiere
     * principale dell'utente.
     *
     * @param  User        $user         Utente per cui risolvere il cantiere.
     * @param  int|null    $punchSiteId  Cantiere impostato manualmente dalla timbratura.
     * @param  Carbon|null $date         Data di riferimento; se null viene usata l'ora corrente.
     * @return int|null                  ID del cantiere risolto oppure null.
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

    /**
     * Restituisce il cantiere assegnato all'utente per la data indicata.
     *
     * La risoluzione avviene controllando prima l'assegnazione attiva e, se
     * assente, il cantiere principale dell'utente. Se nessuno dei due è
     * disponibile, restituisce null.
     *
     * @param  User        $user Utente per cui risolvere il cantiere assegnato.
     * @param  Carbon|null $at   Data di riferimento; se null viene usata l'ora corrente.
     * @return DgSite|null       Modello del cantiere assegnato oppure null.
     */
    public static function resolveAssignedSite(User $user, ?Carbon $at = null): ?DgSite
    {
        $at = $at ?? Carbon::now();

        $assignment = DgSiteAssignment::query()
            ->where('user_id', $user->id)
            ->whereDate('assigned_from', '<=', $at)
            ->where(function ($q) use ($at) {
                $q->whereNull('assigned_to')
                  ->orWhereDate('assigned_to', '>=', $at);
            })
            ->orderByDesc('assigned_from')
            ->first();

        if ($assignment) {
            return $assignment->site;
        }

        return $user->mainSite;
    }
}
