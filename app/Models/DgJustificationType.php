<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DgJustificationType extends Model
{
    protected $table = 'dg_justification_types';
    protected $fillable = ['code','label','requires_doc'];
    protected $casts = ['requires_doc' => 'boolean'];
}
