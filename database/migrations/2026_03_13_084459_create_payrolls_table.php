<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('salary_settings');
        Schema::create('salary_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('base_salary',        15, 2)->default(0);
            $table->decimal('position_allowance', 15, 2)->default(0);
            $table->decimal('overtime_rate',      10, 2)->default(0); // per menit
            $table->decimal('late_rate',          10, 2)->default(0); // per menit
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::dropIfExists('payrolls');
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');

            // Snapshot nama & jabatan saat generate
            $table->string('user_name')->nullable();
            $table->string('user_position')->nullable();

            // Komponen gaji
            $table->decimal('base_salary',               15, 2)->default(0);
            $table->decimal('position_allowance',        15, 2)->default(0);
            $table->decimal('overtime_rate',             10, 2)->default(0);
            $table->decimal('late_rate',                 10, 2)->default(0);

            // Data absensi
            $table->unsignedInteger('total_hadir')->default(0);
            $table->unsignedInteger('total_late_minutes')->default(0);
            $table->unsignedInteger('total_early_leave_minutes')->default(0);
            $table->unsignedInteger('total_overtime_minutes')->default(0);

            // Kalkulasi
            $table->decimal('overtime_pay',   15, 2)->default(0);
            $table->decimal('late_deduction', 15, 2)->default(0);
            $table->decimal('bonus',          15, 2)->default(0);
            $table->decimal('total_salary',   15, 2)->default(0);

            $table->text('note')->nullable();
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('salary_settings');
    
    }
};