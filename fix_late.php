<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$attendances = \App\Models\Attendance::whereDate('date', '>=', '2026-04-01')
    ->whereDate('date', '<=', '2026-04-21')
    ->get();

foreach ($attendances as $att) {
    $checkIn       = \Carbon\Carbon::parse($att->check_in_time);
    $shiftDeadline = \Carbon\Carbon::parse($att->date)->setTime(8, 0, 0);

    $status      = $checkIn->greaterThan($shiftDeadline) ? 'late' : 'on_time';
    $lateMinutes = $status === 'late' ? (int) $checkIn->diffInMinutes($shiftDeadline) : 0;

    // Hitung deduction jika ada data salary
    $salary = \App\Models\Salary::where('user_id', $att->user_id)->first();
    $lateDeductionAmount = $salary ? round($lateMinutes * (float) $salary->late_rate) : 0;

    $att->update([
        'status'                => $status,
        'late_minutes'          => $lateMinutes,
        'late_deduction_amount' => $lateDeductionAmount,
    ]);

    echo "{$att->date} | user_id:{$att->user_id} | masuk:{$checkIn->format('H:i')} | telat:{$lateMinutes} mnt | potongan:{$lateDeductionAmount} | {$status}\n";
}

echo "\n✅ Selesai! late_minutes & deduction sudah diupdate.\n";