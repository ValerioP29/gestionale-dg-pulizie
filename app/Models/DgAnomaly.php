<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class DgAnomaly extends Model
{
    use LogsActivity;

    protected $table = 'dg_anomalies';
    protected $fillable = ['user_id','session_id','date','type','minutes','status','note'];
    protected $casts = ['date' => 'date', 'minutes' => 'integer'];

    public function user(){ return $this->belongsTo(User::class); }
    public function session(){ return $this->belongsTo(DgWorkSession::class, 'session_id'); }
    public function justifications(){ return $this->hasMany(DgUserJustification::class, 'anomaly_id'); }

    public function scopeForPeriod($q, $from, $to){ return $q->whereBetween('date', [$from, $to]); }
    public function scopeForUser($q, $userId){ return $q->where('user_id', $userId); }
    public function scopeType($q, $type){ return $q->where('type', $type); }

    public function markApproved(): void
    {
        $this->status = 'approved';
        $this->approved_at = now();
        $this->approved_by = auth()->id();
        $this->save();

        if ($this->session) {
            // sessione resta completa: non toccare status, no flag rosso
            $flags = $this->session->anomaly_flags ?? [];
            $filtered = array_filter($flags, fn($f) => $f['type'] !== $this->type);
            $this->session->anomaly_flags = array_values($filtered);
            $this->session->save();
        }

        activity('Anomalie')
            ->causedBy(Auth::user())
            ->performedOn($this)
            ->withProperties([
                'anomaly_id' => $this->id,
                'status'     => 'approved',
                'note'       => $this->note,
            ])
            ->log('Anomalia approvata');
    }

    public function markRejected(): void
    {
        $this->status = 'rejected';
        $this->rejected_at = now();
        $this->rejected_by = auth()->id();
        $this->save();

        if ($this->session) {
            // se la respingi, la sessione è "sporco non risolto"
            $s = $this->session;
            if ($s->status === 'complete') {
                $s->status = 'incomplete'; // o 'invalid' se vuoi più severo
            }
            $s->save();
        }
        activity('Anomalie')
            ->causedBy(Auth::user())
            ->performedOn($this)
            ->withProperties([
                'anomaly_id' => $this->id,
                'status'     => 'rejected',
                'reason'     => $motivo,
            ])
            ->log('Anomalia respinta');
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Anomalie');
    }
}
