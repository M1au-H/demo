<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('additional_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');       // pegawai yang dapat tugas
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade'); // admin yang memberi
            $table->string('title');                   // judul tugas
            $table->text('description')->nullable();   // detail tugas
            $table->date('due_date')->nullable();       // deadline
            $table->enum('status', ['pending', 'done'])->default('pending');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_tasks');
    }
};