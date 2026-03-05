<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->unique()->after('id');
            $table->longText('descriptor')->after('user_id');
            $table->string('label')->nullable()->after('descriptor');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('face_profiles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'descriptor', 'label']);
        });
    }
};