<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->integer('total_late_minutes')->default(0)->change();
            $table->integer('total_early_leave_minutes')->default(0)->change();
            $table->integer('total_overtime_minutes')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedInteger('total_late_minutes')->default(0)->change();
            $table->unsignedInteger('total_early_leave_minutes')->default(0)->change();
            $table->unsignedInteger('total_overtime_minutes')->default(0)->change();
        });
    }
};