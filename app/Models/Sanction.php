<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sanction extends Model
{
    protected $fillable = [
        'user_id',
        'given_by',
        'type',
        'reason',
        'sanction_date',
        'proof_photo',
        'completed_at',
        'admin_seen',
    ];

    protected $casts = [
        'sanction_date' => 'date',
        'completed_at'  => 'datetime',
        'admin_seen'    => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function giver()
    {
        return $this->belongsTo(User::class, 'given_by');
    }
}