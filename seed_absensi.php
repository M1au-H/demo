<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

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

// Hanya cuti yang dibatasi 3x
$MAX_CUTI = 3;

// Tracker cuti per user
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

        // Kalau dapat cuti tapi jatah habis, fallback ke hadir
        if ($rand > 93 && $cutiHabis) {
            $rand = 1;
        }

        if ($rand <= 75) {
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
            echo "✓ {$user->name} | {$date} | {$checkIn} - {$checkOut} ({$status}, telat:{$lateMinutes}mnt)\n";

        } elseif ($rand <= 85) {
            // IZIN — tidak ada batas, tapi status pending (butuh persetujuan admin)
            $izinTypes = ['duka_cita','menikah','melahirkan','menemani_istri_melahirkan','khitan','lainnya'];
            $izinType  = $izinTypes[array_rand($izinTypes)];

            // Sebagian approved, sebagian pending (simulasi persetujuan admin)
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
            // SAKIT — tidak ada batas
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
            // CUTI — maksimal 3x per bulan
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

echo "\n✅ Selesai! Aturan: cuti maks {$MAX_CUTI}x | sakit bebas | izin bebas (pending/approved)\n";