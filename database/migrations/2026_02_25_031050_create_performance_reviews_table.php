<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');      // pegawai yang dinilai
            $table->foreignId('reviewed_by')->constrained('users')->onDelete('cascade'); // admin yang menilai
            $table->date('review_date');
            $table->tinyInteger('rating');        // nilai 1-5
            $table->text('comment')->nullable();  // komentar admin
            $table->timestamps();

            $table->index(['user_id', 'review_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};