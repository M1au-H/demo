<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');    // pegawai yang kena sanksi
            $table->foreignId('given_by')->constrained('users')->onDelete('cascade'); // admin yang memberi
            $table->enum('type', [
                'warning',            // Peringatan
                'mandatory_overtime', // Lembur wajib
                'disciplinary_note',  // Catatan disiplin
            ]);
            $table->text('reason');          // alasan sanksi
            $table->date('sanction_date');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanctions');
    }
};