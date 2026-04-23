<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // Ubah default status dari 'approved' ke 'pending' untuk izin
            // Tidak ada perubahan struktur kolom, hanya memastikan kolom sudah ada
            // Kolom cuti_type sudah ada dan akan dipakai untuk izin_type juga
        });
    }

    public function down(): void
    {
        // tidak ada yang perlu di-rollback
    }
};