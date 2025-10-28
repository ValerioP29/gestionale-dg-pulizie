<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DgJobTitle extends Model
{
    protected $table = 'dg_job_titles';

    protected $fillable = [
        'code',
        'name',
        'notes',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
