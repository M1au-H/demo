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
        'status',               // on_time | late
        'late_minutes',
        'late_deduction_amount',

        // Check-out
        'check_out_time',
        'check_out_photo',
        'checkout_status',            // normal | early_leave | overtime
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
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}