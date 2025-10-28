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
        'rules',
        'active',
    ];

    protected $casts = [
        'rules' => 'array', // JSONB -> array PHP
        'active' => 'boolean',
    ];

    // Utenti che usano questo contratto
    public function users()
    {
        return $this->hasMany(User::class, 'contract_schedule_id');
    }
}
