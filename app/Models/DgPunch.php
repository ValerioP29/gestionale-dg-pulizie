<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DgPunch extends Model
{
    use HasFactory;

    protected $table = 'dg_punches';

    // gestiamo manualmente i timestamp
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'site_id',
        'session_id',      // FASE 3: collega la timbratura alla sessione
        'type',            // in / out
        'latitude',
        'longitude',
        'accuracy_m',
        'device_id',
        'device_battery',
        'network_type',
        'source',          // FASE 3: utile se distingui app / import / correzione
        'payload',         // FASE 3: eventuale JSON con dati extra
        'created_at',
        'synced_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'synced_at'  => 'datetime',
        'payload'    => 'array',
    ];

    /* -------- Relations -------- */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(DgSite::class, 'site_id');
    }

    public function session()
    {
        return $this->belongsTo(DgWorkSession::class, 'session_id');
    }

    /* -------- Scopes -------- */

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at');
    }
}
