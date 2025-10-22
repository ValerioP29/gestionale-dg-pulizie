<?php

namespace App\Jobs;

use App\Models\DgPunch;
use App\Models\DgWorkSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class GenerateWorkSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $date;

    public function __construct(?string $date = null)
    {
        $this->date = $date;
    }

    public function handle(): void
    {
        $targetDate = $this->date
            ? Carbon::parse($this->date)->toDateString()
            : now()->subDay()->toDateString();

        $punches = DgPunch::query()
            ->whereDate('created_at', $targetDate)
            ->orderBy('created_at')
            ->get()
            ->groupBy(['user_id', 'site_id']);

        foreach ($punches as $userId => $bySite) {
            foreach ($bySite as $siteId => $records) {
                $checkIn  = $records->firstWhere('type', 'check_in');
                $checkOut = $records->reverse()->firstWhere('type', 'check_out');

                $status = 'invalid';
                $minutes = 0;

                if ($checkIn && $checkOut && $checkOut->created_at->gt($checkIn->created_at)) {
                    $minutes = $checkIn->created_at->diffInMinutes($checkOut->created_at);
                    $status = 'complete';
                } elseif ($checkIn && !$checkOut) {
                    $status = 'incomplete';
                }

                DgWorkSession::updateOrCreate(
                    [
                        'user_id'       => $userId,
                        'site_id'       => $siteId,
                        'session_date'  => $targetDate,
                    ],
                    [
                        'check_in'       => $checkIn?->created_at,
                        'check_out'      => $checkOut?->created_at,
                        'worked_minutes' => $minutes,
                        'status'         => $status,
                        'source'         => 'auto',
                    ]
                );
            }
        }
    }
}
