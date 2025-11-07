<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DgUserJustification extends Model
{
    protected $table = 'dg_user_justifications';

    protected $fillable = [
        'anomaly_id',
        'type_id',
        'created_by',
        'note',
        'attachment_path',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /* ---------- Relations ---------- */
    public function anomaly()
    {
        return $this->belongsTo(DgAnomaly::class, 'anomaly_id');
    }

    public function type()
    {
        return $this->belongsTo(DgJustificationType::class, 'type_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* ---------- Business Logic ---------- */

    public function markApproved(): void
    {
        $this->status = 'approved';
        $this->reviewed_by = auth()->id();
        $this->reviewed_at = Carbon::now();
        $this->save();

        // Aggiorna anomalia collegata
        if ($this->anomaly) {
            // Imposto l'anomalia come giustificata
            $this->anomaly->status = 'justified';
            $this->anomaly->save();

            // Ripulisci la sessione togliendo la flag
            if ($this->anomaly->session) {
                $session = $this->anomaly->session;

                $flags = $session->anomaly_flags ?? [];
                $filtered = array_filter($flags, fn($f) =>
                    is_array($f) && ($f['type'] ?? null) !== $this->anomaly->type
                );

                $session->anomaly_flags = array_values($filtered);
                $session->status = 'complete';
                $session->save();
            }
        }
    }

    public function markRejected(?string $reason = null): void
    {
        $this->status = 'rejected';
        $this->reviewed_by = auth()->id();
        $this->reviewed_at = Carbon::now();

        if ($reason) {
            $this->note = $reason;
        }

        $this->save();

        // Se respinta → l'anomalia resta "open", la sessione resta sporca
        if ($this->anomaly && $this->anomaly->session) {
            $s = $this->anomaly->session;

            if ($s->status === 'complete') {
                $s->status = 'incomplete';
            }

            $s->save();
        }
    }
}
