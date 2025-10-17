<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DgSyncQueue extends Model
{
    use HasFactory;

    protected $table = 'dg_sync_queue';

    protected $fillable = [
        'user_id',
        'uuid',
        'payload',
        'status',
        'synced',
        'error_message',
        'retry_count',
        'synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'synced' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
