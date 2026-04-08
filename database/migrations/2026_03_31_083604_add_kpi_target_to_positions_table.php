<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            // Tambah setelah kolom description
            $table->decimal('kpi_target', 5, 2)->default(100.00)->after('description');
            // 100.00 = default target 100% untuk semua jabatan
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('kpi_target');
        });
    }
};