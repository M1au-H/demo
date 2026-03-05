<?php

namespace App\Helpers;

use App\Models\Notification;
use App\Models\User;

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

    /**
     * Notifikasi ke SEMUA ADMIN ketika pegawai menyelesaikan sanksi.
     */
    public static function sanctionCompleted(int $userId, int $sanctionId, string $type): void
    {
        $pegawai   = User::find($userId);
        $typeLabel = match($type) {
            'warning'            => 'Peringatan',
            'mandatory_overtime' => 'Lembur Wajib',
            'disciplinary_note'  => 'Catatan Disiplin',
            default              => 'Sanksi',
        };

        $name = $pegawai?->name ?? 'Pegawai';

        // Kirim ke semua admin
        $admins = User::where('role', 'admin')->pluck('id');
        foreach ($admins as $adminId) {
            Notification::create_for(
                $adminId,
                'success',
                'sanction',
                'Sanksi Diselesaikan',
                "{$name} telah menyelesaikan sanksi '{$typeLabel}'. Periksa foto buktinya."
            );
        }
    }

    /**
     * Notifikasi ke SEMUA ADMIN ketika pegawai menyelesaikan tugas.
     */
    public static function taskCompleted(int $userId, int $taskId, string $taskTitle): void
    {
        $pegawai = User::find($userId);
        $name    = $pegawai?->name ?? 'Pegawai';

        // Kirim ke semua admin
        $admins = User::where('role', 'admin')->pluck('id');
        foreach ($admins as $adminId) {
            Notification::create_for(
                $adminId,
                'success',
                'task',
                'Tugas Diselesaikan',
                "{$name} telah menyelesaikan tugas \"{$taskTitle}\". Periksa foto buktinya."
            );
        }
    }
}
