<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySetting extends Model
{
    protected $fillable = [
        'user_id',
        'base_salary',
        'position_allowance',
        'overtime_rate',
        'late_rate',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}