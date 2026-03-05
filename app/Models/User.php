<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'api_token',
        'role',
        'phone',
        'avatar',
        'bio',
        'job_title',
        'company',
        'position_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    // ── Relasi ──

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function performanceReviews()
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function sanctions()
    {
        return $this->hasMany(Sanction::class);
    }

    public function additionalTasks()
    {
        return $this->hasMany(AdditionalTask::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function faceProfile()
    {
        return $this->hasOne(FaceProfile::class);
    }

    // ── Helper ──

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }
}