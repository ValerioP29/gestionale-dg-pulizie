<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DgClient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dg_clients';

    protected $fillable = [
        'name',
        'group_id',
        'vat',
        'address',
        'email',
        'phone',
        'active',
        'payroll_client_code',
        'payroll_group_code',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(DgClientGroup::class, 'group_id');
    }

    public function sites()
    {
        return $this->hasMany(DgSite::class, 'client_id');
    }
}
