<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DgPayslip;

class PayslipsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) return;

        DgPayslip::firstOrCreate(
            [
                'user_id' => $user->id,
                'period_year' => now()->year,
                'period_month' => now()->month,
            ],
            [
                'file_name' => 'busta_paga.pdf',
                'file_path' => 'payslips/'.now()->year.'/'.now()->format('m').'/'.$user->id.'/busta_paga.pdf',
                'storage_disk' => 's3',
                'mime_type' => 'application/pdf',
                'file_size' => 123456,
                'checksum' => hash('sha1', 'demo-'.$user->id.'-'.now()->format('Ym')),
                'visible_to_employee' => true,
                'uploaded_by' => $user->id,
                'uploaded_at' => now(),
                'downloads_count' => 0,
            ]
        );
    }
}
