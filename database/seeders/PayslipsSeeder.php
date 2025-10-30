<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DgPayslip;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PayslipsSeeder extends Seeder
{
    public function run(): void
    {
        $employees = User::where('role','employee')->get();
        if ($employees->isEmpty()) {
            $this->command?->error('⚠ Nessun dipendente trovato.');
            return;
        }

        $uploaders = User::whereIn('role', ['admin', 'supervisor'])->pluck('id')->all();
        if (empty($uploaders)) {
            $this->command?->error('⚠ Nessun admin/supervisor: impossibile marcare uploaded_by.');
            return;
        }

        $now = Carbon::now();

        foreach ($employees as $emp) {

            // 12 mesi di cedolini
            for ($i = 0; $i < 12; $i++) {

                $period = $now->copy()->subMonths($i);
                $year = $period->year;
                $month = $period->month;

                // file name più carino
                $fileName = sprintf(
                    'Cedolino-%02d-%d-%s%s.pdf',
                    $month,
                    $year,
                    Str::slug($emp->first_name ?? 'user'),
                    Str::slug($emp->last_name ?? '')
                );

                $filePath = "/fake/payslips/{$year}/{$month}/$fileName";

                // qualcuno scaricato, qualcuno no
                $downloaded = rand(1,100) <= 60; // 60% scaricati
                $downloads = $downloaded ? rand(1, 5) : 0;

                // qualcuno cancellato (soft deletes)
                $deleted = rand(1,100) <= 5; // 5% cestinati

                $payslip = DgPayslip::withTrashed()->updateOrCreate(
                    [
                        'user_id'      => $emp->id,
                        'period_year'  => $year,
                        'period_month' => $month,
                    ],
                    [
                        'file_name'            => $fileName,
                        'file_path'            => $filePath,
                        'storage_disk'         => 'local',
                        'mime_type'            => 'application/pdf',
                        'file_size'            => rand(50_000, 200_000),
                        'checksum'             => md5($emp->id . $year . $month),
                        'visible_to_employee'  => rand(1,100) <= 95,
                        'uploaded_by'          => rand(1,100) <= 80 ? $uploaders[array_rand($uploaders)] : null,
                        'uploaded_at'          => $period->copy()->addDays(rand(1,5)),
                        'downloads_count'      => $downloads,
                        'downloaded_at'        => $downloaded ? $period->copy()->addDays(rand(1,12)) : null,
                        'deleted_at'           => null, // ripristina se era cestinato
                    ]
                );

                if ($deleted) {
                    $payslip->delete();


                }
            }
        }

        $this->command?->info('✅ PayslipsSeeder PRO: 12 mesi, download, delete, visibilità variabile.');
    }
}
