<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DgPayslip extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dg_payslips';

    protected $fillable = [
        'user_id',
        'file_name', 'file_path', 'storage_disk',
        'mime_type', 'file_size', 'checksum',
        'period_year', 'period_month',
        'visible_to_employee',
        'uploaded_by', 'uploaded_at', 'downloaded_at', 'downloads_count',
    ];

    protected $casts = [
        'visible_to_employee' => 'boolean',
        'uploaded_at' => 'datetime',
        'downloaded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Comodo per ordinare e mostrare
    public function getPeriodLabelAttribute(): string
    {
        return sprintf('%02d/%d', $this->period_month, $this->period_year);
    }
}
