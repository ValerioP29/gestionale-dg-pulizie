<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DgUserJustification extends Model
{
    public const CATEGORIES = [
        'vacation' => 'Ferie',
        'leave'    => 'Permesso',
        'sick'     => 'Malattia',
        'other'    => 'Giustificazione',
    ];

    protected $table = 'dg_user_justifications';

    protected $fillable = [
        'anomaly_id',
        'user_id',
        'session_id',
        'date',
        'date_end',
        'category',
        'covers_full_day',
        'minutes',
        'note',
        'attachment_path',
        'status',
        'created_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'date'            => 'date',
        'date_end'        => 'date',
        'covers_full_day' => 'boolean',
        'minutes'         => 'integer',
        'reviewed_at'     => 'datetime',
    ];

    /* ---------- Relations ---------- */
    public function anomaly()
    {
        return $this->belongsTo(DgAnomaly::class, 'anomaly_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function session()
    {
        return $this->belongsTo(DgWorkSession::class, 'session_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* ---------- Scopes ---------- */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCoveringDate($query, CarbonImmutable $date)
    {
        return $query
            ->whereDate('date', '<=', $date->toDateString())
            ->where(function ($inner) use ($date) {
                $inner->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', $date->toDateString());
            });
    }

    /* ---------- Helpers ---------- */
    public function coversDate(CarbonImmutable $date): bool
    {
        $start = CarbonImmutable::parse($this->date);
        $end = $this->date_end
            ? CarbonImmutable::parse($this->date_end)
            : $start;

        return $date->betweenIncluded($start, $end);
    }

    public function coveredMinutesFor(CarbonImmutable $date): int
    {
        if (! $this->coversDate($date)) {
            return 0;
        }

        if ($this->covers_full_day) {
            return 24 * 60;
        }

        return max(0, (int) $this->minutes);
    }

    /* ---------- Business Logic ---------- */
    public function markApproved(?int $actorId = null): void
    {
        $this->status = 'approved';
        $this->reviewed_by = $actorId ?? auth()->id();
        $this->reviewed_at = Carbon::now();
        $this->save();

        if ($this->anomaly) {
            $this->anomaly->status = 'justified';
            $this->anomaly->save();

            if ($this->anomaly->session) {
                $session = $this->anomaly->session;
                $flags = $session->anomaly_flags ?? [];
                $filtered = array_filter($flags, function ($flag) {
                    if (! is_array($flag)) {
                        return true;
                    }

                    return ($flag['type'] ?? null) !== $this->anomaly->type;
                });

                $session->anomaly_flags = array_values($filtered);
                $session->status = 'complete';
                $session->save();
            }
        }
    }

    public function markRejected(?string $reason = null, ?int $actorId = null): void
    {
        $this->status = 'rejected';
        $this->reviewed_by = $actorId ?? auth()->id();
        $this->reviewed_at = Carbon::now();

        if ($reason) {
            $this->note = $reason;
        }

        $this->save();

        if ($this->anomaly && $this->anomaly->session) {
            $session = $this->anomaly->session;
            if ($session->status === 'complete') {
                $session->status = 'incomplete';
            }
            $session->save();
        }
    }
}
