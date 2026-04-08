<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('kpi_score',       5, 2)->nullable()->after('bonus');
            $table->decimal('kpi_target',      5, 2)->nullable()->after('kpi_score');
            $table->decimal('kpi_achievement', 5, 2)->nullable()->after('kpi_target');
            $table->decimal('kpi_adjustment',  15, 2)->default(0)->after('kpi_achievement');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['kpi_score', 'kpi_target', 'kpi_achievement', 'kpi_adjustment']);
        });
    }
};