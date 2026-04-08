<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_settings', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('day_of_week')
                  ->comment('0=Minggu,1=Senin,2=Selasa,3=Rabu,4=Kamis,5=Jumat,6=Sabtu');
            $table->time('start_time')->default('07:00:00');
            $table->time('end_time')->default('16:00:00');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique('day_of_week');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_settings');
    }
};