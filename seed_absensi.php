<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// ══════════════════════════════════════════════════════════════
// BAGIAN 1: DATA ABSENSI 1-20 APRIL (kode lama, tidak diubah)
// ══════════════════════════════════════════════════════════════

// Hapus data lama dulu
\App\Models\Attendance::whereDate('date', '>=', '2026-04-01')
    ->whereDate('date', '<=', '2026-04-20')
    ->delete();

\App\Models\Leave::whereDate('date', '>=', '2026-04-01')
    ->whereDate('date', '<=', '2026-04-20')
    ->delete();

echo "Data lama dihapus.\n";

$users = \App\Models\User::where('role', 'user')->orderBy('id')->get();

$onTimeOptions   = ['07:25','07:30','07:35','07:40','07:45','07:50','07:55','08:00'];
$lateOptions     = ['08:05','08:10','08:15','08:20','08:30','08:45'];
$checkOutOptions = ['16:45','16:50','16:55','17:00','17:05','17:10','17:15','17:20','17:25','17:30','17:35'];

$MAX_CUTI  = 3;
$cutiCount = [];
foreach ($users as $user) {
    $cutiCount[$user->id] = 0;
}

for ($day = 1; $day <= 20; $day++) {
    $date      = '2026-04-' . str_pad($day, 2, '0', STR_PAD_LEFT);
    $dayOfWeek = date('N', strtotime($date));

    if ($dayOfWeek >= 6) {
        echo "Skip {$date} (weekend)\n";
        continue;
    }

    foreach ($users as $user) {
        srand(crc32($user->id . $date));
        $rand = rand(1, 100);

        $cutiHabis = $cutiCount[$user->id] >= $MAX_CUTI;

        if ($rand > 93 && $cutiHabis) {
            $rand = 1;
        }

        if ($rand <= 75) {
            $isLate   = rand(1, 100) <= 20;
            $checkIn  = $isLate
                ? $lateOptions[array_rand($lateOptions)]
                : $onTimeOptions[array_rand($onTimeOptions)];
            $checkOut = $checkOutOptions[array_rand($checkOutOptions)];

            $shiftDeadline = strtotime('08:00');
            $checkInTime   = strtotime($checkIn);
            $status        = $checkInTime > $shiftDeadline ? 'late' : 'on_time';
            $lateMinutes   = $status === 'late' ? (int)(($checkInTime - $shiftDeadline) / 60) : 0;

            $salary        = \App\Models\Salary::where('user_id', $user->id)->first();
            $lateDeduction = $salary ? round($lateMinutes * (float)$salary->late_rate) : 0;

            \App\Models\Attendance::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'check_in_time'         => $date . ' ' . $checkIn . ':00',
                    'check_out_time'        => $date . ' ' . $checkOut . ':00',
                    'status'                => $status,
                    'late_minutes'          => $lateMinutes,
                    'late_deduction_amount' => $lateDeduction,
                ]
            );
            echo "✓ {$user->name} | {$date} | {$checkIn} - {$checkOut} ({$status}, telat:{$lateMinutes}mnt)\n";

        } elseif ($rand <= 85) {
            $izinTypes  = ['duka_cita','menikah','melahirkan','menemani_istri_melahirkan','khitan','lainnya'];
            $izinType   = $izinTypes[array_rand($izinTypes)];
            $izinStatus = rand(1, 100) <= 70 ? 'approved' : 'pending';

            \App\Models\Leave::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'type'      => 'izin',
                    'cuti_type' => $izinType,
                    'status'    => $izinStatus,
                    'reason'    => 'Keperluan ' . $izinType,
                ]
            );
            echo "📋 {$user->name} | {$date} | izin ({$izinType}) [{$izinStatus}]\n";

        } elseif ($rand <= 93) {
            \App\Models\Leave::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'type'      => 'sakit',
                    'cuti_type' => null,
                    'status'    => 'approved',
                    'reason'    => 'Tidak enak badan / sakit',
                ]
            );
            echo "🤒 {$user->name} | {$date} | sakit\n";

        } else {
            \App\Models\Leave::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'type'      => 'cuti',
                    'cuti_type' => null,
                    'status'    => 'approved',
                    'reason'    => 'Cuti tahunan',
                ]
            );
            $cutiCount[$user->id]++;
            echo "🏖️  {$user->name} | {$date} | cuti [{$cutiCount[$user->id]}/{$MAX_CUTI}]\n";
        }
    }
}

echo "\n✅ Selesai bagian 1: Absensi 1-20 April.\n\n";

// ══════════════════════════════════════════════════════════════
// BAGIAN 2: ABSENSI + REVIEW 26 APRIL - 3 MEI 2026
// ══════════════════════════════════════════════════════════════

echo "=== Mengisi data 26 April - 3 Mei 2026 ===\n\n";

// Hapus data lama rentang ini
\App\Models\Attendance::whereDate('date', '>=', '2026-04-26')
    ->whereDate('date', '<=', '2026-05-03')
    ->delete();

\App\Models\Leave::whereDate('date', '>=', '2026-04-26')
    ->whereDate('date', '<=', '2026-05-03')
    ->delete();

// Hapus review lama rentang ini
try {
    \App\Models\PerformanceReview::whereDate('review_date', '>=', '2026-04-26')
        ->whereDate('review_date', '<=', '2026-05-03')
        ->delete();
    echo "Data review lama dihapus.\n";
} catch (\Exception $e) {
    echo "Info: model review tidak ditemukan atau tabel berbeda.\n";
}

$adminId = \App\Models\User::where('role', 'admin')->first()?->id ?? 1;

$reviewComments = [
    1 => [
        'Performa sangat mengecewakan, perlu evaluasi serius.',
        'Produktivitas sangat rendah hari ini.',
    ],
    2 => [
        'Kinerja di bawah ekspektasi, butuh bimbingan lebih.',
        'Masih banyak yang perlu diperbaiki.',
    ],
    3 => [
        'Cukup baik, namun masih perlu peningkatan.',
        'Standar, tidak ada yang menonjol hari ini.',
        'Performa rata-rata, bisa lebih baik lagi.',
    ],
    4 => [
        'Performa baik, konsisten dan dapat diandalkan.',
        'Bekerja dengan baik hari ini, pertahankan.',
        'Hasil kerja memuaskan, hampir sempurna.',
    ],
    5 => [
        'Luar biasa! Produktivitas sangat tinggi hari ini.',
        'Performa terbaik, patut diapresiasi oleh tim.',
        'Sangat memuaskan, menjadi teladan bagi rekan kerja.',
    ],
];

// Buat list tanggal 26 April - 3 Mei
$dates2 = [];
for ($d = 26; $d <= 30; $d++) {
    $dates2[] = '2026-04-' . str_pad($d, 2, '0', STR_PAD_LEFT);
}
for ($d = 1; $d <= 3; $d++) {
    $dates2[] = '2026-05-0' . $d;
}

$totalAbsen  = 0;
$totalReview = 0;

foreach ($dates2 as $date) {
    $dayOfWeek = date('N', strtotime($date));

    if ($dayOfWeek >= 6) {
        echo "Skip {$date} (weekend)\n";
        continue;
    }

    echo "\n--- {$date} ---\n";

    foreach ($users as $user) {
        srand(crc32($user->id . $date . 'v2'));
        $rand = rand(1, 100);

        // 78% hadir, 10% izin, 7% sakit, 5% cuti
        if ($rand <= 78) {
            // HADIR
            $isLate   = rand(1, 100) <= 20;
            $checkIn  = $isLate
                ? $lateOptions[array_rand($lateOptions)]
                : $onTimeOptions[array_rand($onTimeOptions)];
            $checkOut = $checkOutOptions[array_rand($checkOutOptions)];

            $shiftDeadline = strtotime('08:00');
            $checkInTime   = strtotime($checkIn);
            $status        = $checkInTime > $shiftDeadline ? 'late' : 'on_time';
            $lateMinutes   = $status === 'late' ? (int)(($checkInTime - $shiftDeadline) / 60) : 0;

            $salary        = \App\Models\Salary::where('user_id', $user->id)->first();
            $lateDeduction = $salary ? round($lateMinutes * (float)$salary->late_rate) : 0;

            \App\Models\Attendance::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'check_in_time'         => $date . ' ' . $checkIn . ':00',
                    'check_out_time'        => $date . ' ' . $checkOut . ':00',
                    'status'                => $status,
                    'late_minutes'          => $lateMinutes,
                    'late_deduction_amount' => $lateDeduction,
                ]
            );
            echo "✓ {$user->name} | masuk:{$checkIn} - pulang:{$checkOut} ({$status})\n";
            $totalAbsen++;

            // Isi review untuk yang hadir (85% kemungkinan dinilai)
            $doReview = rand(1, 100) <= 85;
            if ($doReview) {
                $ratingRand = rand(1, 100);
                $rating = match(true) {
                    $ratingRand <= 5  => 1,
                    $ratingRand <= 15 => 2,
                    $ratingRand <= 40 => 3,
                    $ratingRand <= 80 => 4,
                    default           => 5,
                };
                $commentPool = $reviewComments[$rating];
                $comment     = $commentPool[array_rand($commentPool)];

                try {
                    \App\Models\PerformanceReview::updateOrCreate(
                        ['user_id' => $user->id, 'review_date' => $date],
                        [
                            'reviewer_id' => $adminId,
                            'rating'      => $rating,
                            'comment'     => $comment,
                        ]
                    );
                    $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                    echo "  ⭐ Review: {$stars} ({$rating}/5) — {$comment}\n";
                    $totalReview++;
                } catch (\Exception $e) {
                    echo "  ⚠ Gagal simpan review: " . $e->getMessage() . "\n";
                }
            }

        } elseif ($rand <= 88) {
            // IZIN
            $izinTypes  = ['duka_cita','menikah','melahirkan','menemani_istri_melahirkan','khitan','lainnya'];
            $izinType   = $izinTypes[array_rand($izinTypes)];
            $izinStatus = rand(1, 100) <= 70 ? 'approved' : 'pending';

            \App\Models\Leave::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'type'      => 'izin',
                    'cuti_type' => $izinType,
                    'status'    => $izinStatus,
                    'reason'    => 'Keperluan ' . $izinType,
                ]
            );
            echo "📋 {$user->name} | izin ({$izinType}) [{$izinStatus}]\n";

        } elseif ($rand <= 95) {
            // SAKIT
            \App\Models\Leave::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'type'      => 'sakit',
                    'cuti_type' => null,
                    'status'    => 'approved',
                    'reason'    => 'Tidak enak badan / sakit',
                ]
            );
            echo "🤒 {$user->name} | sakit\n";

        } else {
            // CUTI
            \App\Models\Leave::updateOrCreate(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'type'      => 'cuti',
                    'cuti_type' => null,
                    'status'    => 'approved',
                    'reason'    => 'Cuti tahunan',
                ]
            );
            echo "🏖️  {$user->name} | cuti\n";
        }
    }
}

echo "\n✅ Selesai bagian 2!\n";
echo "   Total absensi : {$totalAbsen}\n";
echo "   Total review  : {$totalReview}\n";
echo "\n🎉 Semua data berhasil diisi.\n";