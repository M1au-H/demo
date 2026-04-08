<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // Cek dulu kalau kolom belum ada agar tidak error jika dijalankan ulang
            if (!Schema::hasColumn('leaves', 'surat_dokter')) {
                $table->string('surat_dokter')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('surat_dokter');
        });
    }
};