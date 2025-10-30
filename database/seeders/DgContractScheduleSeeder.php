<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgContractSchedule;

class DgContractScheduleSeeder extends Seeder
{
    public function run(): void
    {
        DgContractSchedule::query()->delete();

        $patterns = [
            [
                'name' => 'Full-time 120h',
                'mon'=>5,'tue'=>5,'wed'=>5,'thu'=>5,'fri'=>5,'sat'=>5,'sun'=>0
            ],
            [
                'name' => 'Part-time 80h',
                'mon'=>4,'tue'=>4,'wed'=>4,'thu'=>4,'fri'=>4,'sat'=>0,'sun'=>0
            ],
            [
                'name' => 'Mini-20h',
                'mon'=>1,'tue'=>1,'wed'=>1,'thu'=>1,'fri'=>1,'sat'=>0,'sun'=>0
            ],
        ];

        foreach ($patterns as $p) {

            $rules = [
                'mon' => ['start'=>'08:00', 'end'=>'12:00', 'break'=>0],
                'tue' => ['start'=>'08:00', 'end'=>'12:00', 'break'=>0],
                'wed' => ['start'=>'08:00', 'end'=>'12:00', 'break'=>0],
                'thu' => ['start'=>'08:00', 'end'=>'12:00', 'break'=>0],
                'fri' => ['start'=>'08:00', 'end'=>'12:00', 'break'=>0],
                'sat' => null,
                'sun' => null,
            ];

            $total = ($p['mon'] + $p['tue'] + $p['wed'] + $p['thu'] + $p['fri'] + $p['sat'] + $p['sun']) * 4;

            DgContractSchedule::updateOrCreate(
                ['name' => $p['name']],
                [
                    'mon' => $p['mon'],
                    'tue' => $p['tue'],
                    'wed' => $p['wed'],
                    'thu' => $p['thu'],
                    'fri' => $p['fri'],
                    'sat' => $p['sat'],
                    'sun' => $p['sun'],
                    'contract_hours_monthly' => $total,
                    'rules' => $rules,
                    'active' => true,
                ]
            );
        }

        $this->command?->info('✅ Contratti con regole e ore mensili generati.');
    }
}
