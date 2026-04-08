<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiScore extends Model
{
    protected $fillable = [
        'user_id', 'month', 'year',
        'attendance_score', 'task_score', 'admin_rating',
        'final_kpi', 'kpi_target', 'achievement',
        'kpi_grade', 'kpi_adjustment',
        'notes', 'rated_by',
    ];

    // ── Relasi ──

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ratedBy()
    {
        return $this->belongsTo(User::class, 'rated_by');
    }

    // ── Helper static ──

    /**
     * Hitung achievement: seberapa % KPI memenuhi target jabatan
     * Contoh: KPI 88, target 110 → achievement = 80%
     */
    public static function calcAchievement(float $finalKpi, float $kpiTarget): float
    {
        if ($kpiTarget <= 0) return 0;
        return round(($finalKpi / $kpiTarget) * 100, 2);
    }

    /**
     * Grade berdasarkan achievement (bukan raw KPI)
     */
    public static function calcGrade(float $achievement): string
    {
        if ($achievement >= 100) return 'A';
        if ($achievement >= 85)  return 'B';
        if ($achievement >= 70)  return 'C';
        return 'D';
    }

    /**
     * Hitung adjustment gaji dari salary_settings user
     * Pakai base_salary + position_allowance sebagai dasar
     */
    public static function calcAdjustment(float $achievement, float $baseSalary, float $posAllowance): float
    {
        $total = $baseSalary + $posAllowance;

        if ($achievement >= 100) return round($total * 0.10, 2);   // +10% bonus
        if ($achievement >= 85)  return 0;                          // normal
        if ($achievement >= 70)  return -round($total * 0.05, 2);  // -5%
        return -round($total * 0.10, 2);                            // -10%
    }
}