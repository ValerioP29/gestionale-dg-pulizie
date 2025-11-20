<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DgContractSchedule extends Model
{
    use HasFactory;

    protected $table = 'dg_contract_schedules';

    protected $fillable = [
        'name',
        'mon','tue','wed','thu','fri','sat','sun',
        'contract_hours_monthly',
        'break_minutes',
        'rules',
        'active',
    ];

    protected $casts = [
        'rules' => 'array',
        'active' => 'boolean',
        'mon' => 'float',
        'tue' => 'float',
        'wed' => 'float',
        'thu' => 'float',
        'fri' => 'float',
        'sat' => 'float',
        'sun' => 'float',
        'break_minutes' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'contract_schedule_id');
    }
}
