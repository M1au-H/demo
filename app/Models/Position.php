<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'name',
        'description',
        'kpi_target', // ← tambah ini saja
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}