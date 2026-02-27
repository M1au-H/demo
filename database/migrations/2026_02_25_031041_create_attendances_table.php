<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');                      // tanggal absen

            // Check-in
            $table->time('check_in_time')->nullable();
            $table->string('check_in_photo')->nullable();  // path foto di storage

            // Check-out
            $table->time('check_out_time')->nullable();
            $table->string('check_out_photo')->nullable();

            // Status masuk
            $table->enum('status', ['on_time', 'late', 'absent'])->default('on_time');

            // Status pulang
            $table->enum('checkout_status', ['normal', 'early_leave', 'overtime'])->nullable();

            // Menit keterlambatan & lembur
            $table->integer('late_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);

            // Flag jika admin edit manual
            $table->boolean('is_edited')->default(false);
            $table->text('edit_note')->nullable();

            $table->timestamps();

            // Satu user hanya bisa absen 1x per hari
            $table->unique(['user_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};