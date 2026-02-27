<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'category',
        'title',
        'message',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper static method untuk buat notifikasi
    public static function create_for(int $userId, string $type, string $category, string $title, string $message): self
    {
        return self::create([
            'user_id'  => $userId,
            'type'     => $type,
            'category' => $category,
            'title'    => $title,
            'message'  => $message,
            'is_read'  => false,
        ]);
    }
}