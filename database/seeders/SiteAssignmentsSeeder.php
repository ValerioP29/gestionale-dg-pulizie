<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SiteAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        $employeeIds = DB::table('users')->where('role','employee')->pluck('id')->all();
        $siteIds     = DB::table('dg_sites')->pluck('id')->all();
        $adminId     = DB::table('users')->where('role','admin')->value('id');

        $startBase = Carbon::now()->startOfMonth()->subMonths(1); // dal mese scorso
        foreach ($employeeIds as $idx => $uid) {
            // a ciascuno 1-2 cantieri
            $primarySite = $siteIds[$idx % max(count($siteIds),1)] ?? null;
            $secondarySite = $siteIds[($idx+3) % max(count($siteIds),1)] ?? null;

            if ($primarySite) {
                DB::table('dg_site_assignments')->updateOrInsert(
                    ['user_id'=>$uid,'site_id'=>$primarySite,'assigned_from'=>$startBase->copy()->toDateString()],
                    [
                        'assigned_to'=>null,
                        'assigned_by'=>$adminId,
                        'notes'=>null,
                        'created_at'=>Carbon::now(),
                        'updated_at'=>Carbon::now(),
                    ]
                );

                // setta main_site per l'utente
                DB::table('users')->where('id',$uid)->update(['main_site_id' => $primarySite]);
            }

            // circa metà hanno anche un secondo cantiere temporaneo
            if ($secondarySite && $idx % 2 === 0) {
                DB::table('dg_site_assignments')->updateOrInsert(
                    [
                        'user_id'=>$uid,
                        'site_id'=>$secondarySite,
                        'assigned_from'=>$startBase->copy()->addDays(10)->toDateString()
                    ],
                    [
                        'assigned_to'=> $startBase->copy()->addDays(25)->toDateString(),
                        'assigned_by'=>$adminId,
                        'notes'=>'temporaneo',
                        'created_at'=>Carbon::now(),
                        'updated_at'=>Carbon::now(),
                    ]
                );
            }
        }
    }
}
