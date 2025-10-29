<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DgClientGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dg_client_groups';

    protected $fillable = [
        'name',
        'notes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function clients()
    {
        return $this->hasMany(DgClient::class, 'group_id');
    }
}
