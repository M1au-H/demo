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
    ];

    protected $casts = [
        'sanction_date' => 'date',
    ];

    // Pegawai yang kena sanksi
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Admin yang memberi sanksi
    public function giver()
    {
        return $this->belongsTo(User::class, 'given_by');
    }
}