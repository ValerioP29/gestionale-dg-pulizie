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
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_m' => 'integer',
        'active' => 'boolean',
        'type' => 'string',
    ];

    // Relazione: cantiere → assegnazioni
    public function assignments()
    {
        return $this->hasMany(DgSiteAssignment::class, 'site_id');
    }

    // Relazione: cantiere → utenti (tramite pivot)
    public function users()
    {
        return $this->belongsToMany(User::class, 'dg_site_assignments', 'site_id', 'user_id')
                    ->withPivot(['assigned_from', 'assigned_to', 'notes', 'assigned_by'])
                    ->withTimestamps();
    }

    public function punches()
    {
        return $this->hasMany(DgPunch::class, 'site_id');
    }

    public function workSessions()
    {
        return $this->hasMany(DgWorkSession::class, 'site_id');
    }

    protected static function booted()
{
    static::saving(function ($site) {
        if ($site->isDirty('address') && !empty($site->address)) {
            $coords = self::geocodeAddress($site->address);
            if ($coords) {
                $site->latitude = $coords['lat'];
                $site->longitude = $coords['lng'];
            }
        }
    });
}

    public static function geocodeAddress(string $address): ?array
    {
        $apiKey = config('services.google_maps.key');
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $apiKey;

        $response = @file_get_contents($url);
        if (!$response) return null;

        $data = json_decode($response, true);
        if (!empty($data['results'][0]['geometry']['location'])) {
            return $data['results'][0]['geometry']['location'];
        }

        return null;
    }


}
