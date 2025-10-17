<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DgSiteAssignment extends Model
{
    use HasFactory;

    protected $table = 'dg_site_assignments';

    protected $fillable = [
        'user_id',
        'site_id',
        'assigned_from',
        'assigned_to',
        'assigned_by',
        'notes',
    ];

    protected $casts = [
        'assigned_from' => 'date',
        'assigned_to' => 'date',
    ];

    // Relazione: assegnazione → utente assegnato
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relazione: assegnazione → cantiere
    public function site()
    {
        return $this->belongsTo(DgSite::class, 'site_id');
    }

    // Relazione: chi ha assegnato (utente admin/HR)
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
