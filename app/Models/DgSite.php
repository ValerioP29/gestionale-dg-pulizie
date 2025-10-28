<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DgSite extends Model
{
    use HasFactory;

    protected $table = 'dg_sites';

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'radius_m',
        'active',
        'type',
        'client_id', // utile se vuoi legarlo al cliente nel form Filament
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        'radius_m'  => 'integer',
        'active'    => 'boolean',
        'type'      => 'string',
        'anomaly_flags' => 'array',
        'session_date' => 'date',
    ];

    /* -------- Relazioni -------- */

    public function assignments()
    {
        return $this->hasMany(DgSiteAssignment::class, 'site_id');
    }

    public function workSessions()
    {
        return $this->hasMany(DgWorkSession::class, 'site_id');
    }

    public function punches()
    {
        return $this->hasMany(DgPunch::class, 'site_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'dg_site_assignments', 'site_id', 'user_id')
                    ->withPivot(['assigned_from', 'assigned_to', 'notes', 'assigned_by'])
                    ->withTimestamps();
    }

    public function client()
    {
        return $this->belongsTo(DgClient::class);
    }

    /* -------- Geocoding automatico -------- */

    protected static function booted()
    {
        static::saving(function ($site) {
            if ($site->isDirty('address') && !empty($site->address)) {
                $coords = self::geocodeAddress($site->address);
                if ($coords) {
                    $site->latitude  = $coords['lat'];
                    $site->longitude = $coords['lng'];
                }
            }
        });
    }

    public static function geocodeAddress(string $address): ?array
    {
        $apiKey = config('services.google_maps.key'); // chiave centralizzata
        if (!$apiKey) return null;

        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $apiKey;
        $response = @file_get_contents($url);
        if (!$response) return null;

        $data = json_decode($response, true);
        return $data['results'][0]['geometry']['location'] ?? null;
    }


    public function getHasAnomaliesAttribute(): bool
        {
            return !empty($this->anomaly_flags);
        }

        public function getAnomalySummaryAttribute(): string
        {
            if (!$this->anomaly_flags) return 'Nessuna anomalia';
            return collect($this->anomaly_flags)
                ->map(fn ($i) => ($i['type'] ?? '') . ' (' . ($i['minutes'] ?? 0) . ' min)')
                ->join(' | ');
    }
}
