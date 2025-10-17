<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DgUserConsent extends Model
{
    use HasFactory;

    protected $table = 'dg_user_consents';

    protected $fillable = [
        'user_id',
        'type',
        'accepted',
        'accepted_at',
        'revoked_at',
        'source',
    ];

    protected $casts = [
        'accepted' => 'boolean',
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
