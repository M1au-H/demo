<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'user_id', 'month', 'year',
        'base_salary', 'position_allowance',
        'overtime_pay', 'late_deduction',
        'bonus', 'sanction_deduction',
        'total_salary',
        'total_hadir', 'total_late_minutes',
        'total_early_leave_minutes', 'total_overtime_minutes',
        'status', 'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}