<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;

class DgSite extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'dg_sites';

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'radius_m',
        'active',
        'type',
        'client_id',
        'payroll_site_code',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        'radius_m'  => 'integer',
        'active'    => 'boolean',
        'type'      => 'string',
        'anomaly_flags' => 'array',
    ];

    /* -------- Relazioni -------- */
    public function client()
    {
        return $this->belongsTo(DgClient::class, 'client_id');
    }

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

    /* -------- Geocoding automatico -------- */
    protected static function booted()
    {
        static::saving(function ($site) {
            if ($site->isDirty('address') && is_string($site->address) && trim($site->address) !== '') {

                $coords = self::geocodeAddress($site->address);
                if ($coords) {
                    $site->latitude  = $coords['lat'];
                    $site->longitude = $coords['lng'];
                }
            }
        });
    }

    public static function geocodeAddress($address): ?array
{
    // Non è una stringa? non geocodiamo nulla
    if (!is_string($address) || trim($address) === '') {
        return null;
    }

    $apiKey = config('services.google_maps.key');
    if (!$apiKey) return null;

    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $apiKey;
    $response = @file_get_contents($url);
    if (!$response) return null;

    $data = json_decode($response, true);
    return $data['results'][0]['geometry']['location'] ?? null;
}


    /* -------- Helpers per anomalie -------- */
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

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Cantieri');
    }
}
