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

    public function punch(Request $request)
    {
        $data = $request->validate([
            'type'              => 'required|in:in,out',
            'device_latitude'   => 'nullable|numeric',
            'device_longitude'  => 'nullable|numeric',
            'device_accuracy_m' => 'nullable|numeric',
        ]);

        $user = $request->user();

        $site = SiteResolverService::resolveAssignedSite($user);

        $session = DgWorkSession::where('user_id', $user->id)
            ->whereNull('check_out')
            ->orderByDesc('check_in')
            ->first();

        $hasDeviceCoords = isset($data['device_latitude'], $data['device_longitude']);
        $distance = null;
        $outside = false;

        if (!$site && $session) {
            $site = $session->site;
        }

        if (!$site) {
            return response()->json([
                'status'  => 'error',
                'code'    => 'site_missing',
                'message' => 'Nessun cantiere assegnato',
            ], 422);
        }

        if ($hasDeviceCoords) {
            $earthRadius = 6371000; // meters

            $latFrom = deg2rad($data['device_latitude']);
            $lonFrom = deg2rad($data['device_longitude']);
            $latTo   = deg2rad($site->latitude);
            $lonTo   = deg2rad($site->longitude);

            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;

            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2))));
            $distance = $earthRadius * $angle;
            $outside = $distance > $site->radius_m;

            if ($outside) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'outside_site',
                    'message' => 'Sei fuori cantiere, impossibile registrare la timbratura.',
                    'data'    => [
                        'distance_m' => $distance,
                        'radius_m'   => $site->radius_m,
                    ],
                ], 422);
            }
        }

        if ($data['type'] === 'in') {
            if ($session) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Sessione di lavoro già attiva',
                ], 409);
            }

            $session = DgWorkSession::create([
                'user_id'            => $user->id,
                'site_id'            => $site?->id,
                'session_date'       => now()->toDateString(),
                'check_in'           => now(),
                'device_latitude'    => $data['device_latitude'] ?? null,
                'device_longitude'   => $data['device_longitude'] ?? null,
                'device_accuracy_m'  => $data['device_accuracy_m'] ?? null,
                'device_distance_m'  => $distance,
                'outside_site'       => $outside,
            ]);
        } else {
            if (!$session) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Nessuna sessione di lavoro attiva da chiudere',
                ], 409);
            }

            $session->update([
                'check_out'          => now(),
                'device_latitude'    => $data['device_latitude'] ?? null,
                'device_longitude'   => $data['device_longitude'] ?? null,
                'device_accuracy_m'  => $data['device_accuracy_m'] ?? null,
                'device_distance_m'  => $distance,
                'outside_site'       => $outside,
            ]);

            $session->refresh();
        }

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
                'assigned_site' => $assignedSiteData,
                'session'       => $session,
                'warnings'      => [],
            ],
        ]);
    }
}
