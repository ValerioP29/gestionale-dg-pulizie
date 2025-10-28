<?php
namespace App\Observers;

use App\Models\DgWorkSession;
use App\Services\SiteResolverService;
use Carbon\Carbon;

class DgWorkSessionObserver
{
    public function saving(DgWorkSession $session): void
    {
        if ($session->user) {
            $session->resolved_site_id = SiteResolverService::resolveFor(
                $session->user,
                $session->site_id,
                Carbon::parse($session->session_date)
            );
        }
    }
}
