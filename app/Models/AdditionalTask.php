<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalTask extends Model
{
    protected $fillable = [
        'user_id',
        'assigned_by',
        'title',
        'description',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    // Pegawai yang dapat tugas
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Admin yang memberi tugas
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}   