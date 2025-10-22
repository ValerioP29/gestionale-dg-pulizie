<?php

namespace App\Jobs;

use App\Models\DgWorkSession;
use App\Models\DgReportCache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class GenerateReportsCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $periodStart;
    protected ?string $periodEnd;

    public function __construct(?string $periodStart = null, ?string $periodEnd = null)
    {
        $this->periodStart = $periodStart;
        $this->periodEnd = $periodEnd;
    }

    public function handle(): void
    {
        $start = $this->periodStart ? Carbon::parse($this->periodStart) : now()->startOfMonth();
        $end   = $this->periodEnd   ? Carbon::parse($this->periodEnd)   : now()->endOfMonth();

        $sessions = DgWorkSession::query()
            ->whereBetween('session_date', [$start, $end])
            ->get()
            ->groupBy(['user_id', 'site_id']);

        foreach ($sessions as $userId => $bySite) {
            foreach ($bySite as $siteId => $records) {
                $workedHours = round($records->sum('worked_minutes') / 60, 2);
                $daysPresent = $records->where('status', 'complete')->count();
                $daysAbsent  = $records->where('status', 'invalid')->count();
                $lateEntries = 0;
                $earlyExits  = 0;

                DgReportCache::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'site_id' => $siteId,
                        'period_start' => $start->toDateString(),
                        'period_end' => $end->toDateString(),
                    ],
                    [
                        'worked_hours' => $workedHours,
                        'days_present' => $daysPresent,
                        'days_absent' => $daysAbsent,
                        'late_entries' => $lateEntries,
                        'early_exits' => $earlyExits,
                        'generated_at' => now(),
                        'is_final' => false,
                    ]
                );
            }
        }
    }
}
