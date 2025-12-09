<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\DgWorkSession;
use App\Services\SiteResolverService;
use Illuminate\Http\Request;

class WorkSessionController
{
    public function current(Request $request)
    {
        $user = $request->user();

        $site = SiteResolverService::resolveAssignedSite($user);

        $session = DgWorkSession::where('user_id', $user->id)
            ->whereNull('check_out')
            ->orderByDesc('check_in')
            ->first();

        $assignedSiteData = $site ? [
            'id'         => $site->id,
            'name'       => $site->name,
            'latitude'   => $site->latitude,
            'longitude'  => $site->longitude,
            'radius_m'   => $site->radius_m,
        ] : null;

        return response()->json([
            'status' => 'ok',
            'data'   => [
                'assigned_site'      => $assignedSiteData,
                'has_active_session' => $session !== null,
                'session'            => $session,
            ],
        ]);
    }
}
