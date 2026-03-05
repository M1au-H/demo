<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceProfile extends Model
{
    protected $fillable = ['user_id', 'descriptor', 'label'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}