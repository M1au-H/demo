<?php

namespace App\Helpers;

use App\Models\Notification;

class NotificationHelper
{
    /**
     * Notifikasi ketika admin memberi penilaian performa.
     */
    public static function performanceReview(int $userId, int $rating): void
    {
        $label = match(true) {
            $rating >= 5 => 'Sangat Baik ⭐⭐⭐⭐⭐',
            $rating >= 4 => 'Baik ⭐⭐⭐⭐',
            $rating >= 3 => 'Cukup ⭐⭐⭐',
            $rating >= 2 => 'Kurang ⭐⭐',
            default      => 'Perlu Peningkatan ⭐',
        };

        Notification::create_for(
            $userId,
            $rating >= 3 ? 'success' : 'warning',
            'performance',
            'Penilaian Performa Baru',
            "Kamu mendapat penilaian performa: {$label}."
        );
    }

    /**
     * Notifikasi ketika admin memberi sanksi.
     */
    public static function sanction(int $userId, string $type, string $reason): void
    {
        $typeLabel = match($type) {
            'warning'            => 'Peringatan',
            'mandatory_overtime' => 'Lembur Wajib',
            'disciplinary_note'  => 'Catatan Disiplin',
            default              => 'Sanksi',
        };

        Notification::create_for(
            $userId,
            'warning',
            'sanction',
            "Sanksi Baru: {$typeLabel}",
            "Kamu mendapat sanksi '{$typeLabel}'. Alasan: {$reason}"
        );
    }

    /**
     * Notifikasi ketika admin memberi tugas tambahan.
     */
    public static function additionalTask(int $userId, string $title, ?string $dueDate): void
    {
        $dueTxt = $dueDate ? " Deadline: {$dueDate}." : '';

        Notification::create_for(
            $userId,
            'info',
            'task',
            'Tugas Baru Diberikan',
            "Kamu mendapat tugas baru: \"{$title}\".{$dueTxt}"
        );
    }
}