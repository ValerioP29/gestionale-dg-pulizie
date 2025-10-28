<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class PayslipsSeeder extends Seeder
{
    public function run(): void
    {
        $employees = User::where('role','employee')->pluck('id')->all();
        $now = Carbon::now();

        foreach ($employees as $uid) {
            DB::table('dg_payslips')->updateOrInsert(
                ['user_id'=>$uid,'period_year'=>$now->year,'period_month'=>$now->month],
                [
                    'file_name'=>"payslip_{$uid}_{$now->format('Ym')}.pdf",
                    'file_path'=>"/fake/payslips/payslip_{$uid}_{$now->format('Ym')}.pdf",
                    'storage_disk'=>'local',
                    'mime_type'=>'application/pdf',
                    'file_size'=>rand(50_000, 120_000),
                    'checksum'=>md5($uid.$now->format('Ym')),
                    'visible_to_employee'=>true,
                    'uploaded_by'=>1,
                    'uploaded_at'=>$now,
                    'downloads_count'=>0,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ]
            );
        }
    }
}
