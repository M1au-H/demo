<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    protected $fillable = [
        'user_id',
        'reviewed_by',
        'review_date',
        'rating',
        'comment',
    ];

    protected $casts = [
        'review_date' => 'date',
        'rating'      => 'integer',
    ];

    // Pegawai yang dinilai
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Admin yang menilai
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}