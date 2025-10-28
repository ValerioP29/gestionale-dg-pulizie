<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DgUserJustification extends Model
{
    protected $table = 'dg_user_justifications';
    protected $fillable = ['anomaly_id','type_id','created_by','note','attachment_path'];

    public function anomaly(){ return $this->belongsTo(DgAnomaly::class, 'anomaly_id'); }
    public function type(){ return $this->belongsTo(DgJustificationType::class, 'type_id'); }
    public function author(){ return $this->belongsTo(User::class, 'created_by'); }
}
