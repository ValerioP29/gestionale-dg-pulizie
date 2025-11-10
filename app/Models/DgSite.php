<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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
        if (!is_string($address) || trim($address) === '') {
            return null;
        }

        $apiKey = config('services.google_maps.key');

        if (!$apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'key'     => $apiKey,
                ]);
        } catch (ConnectionException $e) {
            logger()->warning('Geocode connection failure', [
                'address' => $address,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }

        if ($response->failed()) {
            logger()->warning('Geocode failed', [
                'address' => $address,
                'status'  => $response->status(),
                'body'    => config('app.debug') ? $response->body() : null,
            ]);

            return null;
        }

        $data = $response->json();

        if (!is_array($data) || ($data['status'] ?? null) !== 'OK') {
            logger()->warning('Geocode unexpected payload', [
                'address' => $address,
                'payload' => config('app.debug') ? $data : null,
            ]);

            return null;
        }

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
