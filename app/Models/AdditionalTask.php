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
        'proof_photo',
        'completed_at',
        'admin_seen',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
        'admin_seen'   => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}