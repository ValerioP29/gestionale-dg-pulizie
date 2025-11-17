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

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Anomalie');
    }
}
