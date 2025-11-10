<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DgPunch extends Model
{
    use HasFactory;

    protected $table = 'dg_punches';
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'site_id',
        'session_id',
        'type',            // 'check_in' | 'check_out'
        'latitude',
        'longitude',
        'accuracy_m',
        'device_id',
        'device_battery',
        'network_type',
        'source',
        'payload',
        'created_at',
        'synced_at',
        'updated_at',
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
    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    public function scopeForSite($q, int $siteId)
    {
        return $q->where('site_id', $siteId);
    }

    public function scopeBetweenDates($q, $from, $to)
    {
        return $q->whereBetween('created_at', [$from, $to]);
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('created_at');
    }

    /* -------- Helper per offline -------- */
    public function punchInstant(): Carbon
    {
        $p = $this->payload ?? [];
        $candidate = $p['punched_at'] ?? $p['client_ts'] ?? null;

        return $candidate
            ? Carbon::parse($candidate)
            : ($this->created_at instanceof \DateTimeInterface
                ? Carbon::parse($this->created_at)
                : now());
    }

    /* -------- UUID automatico -------- */
    protected static function booted(): void
    {
        static::creating(function ($p) {
            if (empty($p->uuid)) {
                $p->uuid = Str::uuid()->toString();
            }

            if (empty($p->created_at)) {
                $p->created_at = now();
            }
        });
    }
}
