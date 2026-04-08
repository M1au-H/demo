<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');

            // Komponen KPI (0-100 masing-masing)
            $table->decimal('attendance_score', 5, 2)->default(0);
            $table->decimal('task_score',       5, 2)->default(0);
            $table->decimal('admin_rating',     5, 2)->default(80);

            // Hasil akhir
            $table->decimal('final_kpi',    5, 2)->default(0); // rata-rata 3 komponen
            $table->decimal('kpi_target',   5, 2)->default(100); // snapshot target jabatan
            $table->decimal('achievement',  5, 2)->default(0);   // final_kpi/kpi_target × 100

            // Grade & efek ke gaji
            $table->string('kpi_grade')->nullable();             // A, B, C, D
            $table->decimal('kpi_adjustment', 15, 2)->default(0); // + bonus atau - potongan

            $table->text('notes')->nullable();
            $table->foreignId('rated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'month', 'year']); // 1 KPI per orang per bulan
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_scores');
    }
};