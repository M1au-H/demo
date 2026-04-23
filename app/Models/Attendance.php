<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',

        // Check-in
        'check_in_time',
        'check_in_photo',
        'check_in_lat',           // ✅ tambahan GPS
        'check_in_lng',           // ✅ tambahan GPS
        'status',                 // on_time | late
        'late_minutes',
        'late_deduction_amount',

        // Check-out
        'check_out_time',
        'check_out_photo',
        'check_out_lat',          // ✅ tambahan GPS
        'check_out_lng',          // ✅ tambahan GPS
        'checkout_status',        // normal | early_leave | overtime
        'early_leave_minutes',
        'early_leave_deduction_amount',
        'overtime_minutes',
        'overtime_pay_amount',

        // Admin edit
        'is_edited',
        'edit_note',
    ];

    protected $casts = [
        'date'                         => 'date',
        'is_edited'                    => 'boolean',
        'late_deduction_amount'        => 'decimal:2',
        'early_leave_deduction_amount' => 'decimal:2',
        'overtime_pay_amount'          => 'decimal:2',
        'check_in_lat'                 => 'decimal:7',  // ✅
        'check_in_lng'                 => 'decimal:7',  // ✅
        'check_out_lat'                => 'decimal:7',  // ✅
        'check_out_lng'                => 'decimal:7',  // ✅
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}