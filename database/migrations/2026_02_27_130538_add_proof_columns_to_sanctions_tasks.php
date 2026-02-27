<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sanctions', function (Blueprint $table) {
            $table->string('proof_photo')->nullable()->after('reason');
            $table->timestamp('completed_at')->nullable()->after('proof_photo');
        });

        Schema::table('additional_tasks', function (Blueprint $table) {
            $table->string('proof_photo')->nullable()->after('description');
            $table->timestamp('completed_at')->nullable()->after('proof_photo');
        });
    }

    public function down(): void
    {
        Schema::table('sanctions', function (Blueprint $table) {
            $table->dropColumn(['proof_photo', 'completed_at']);
        });
        Schema::table('additional_tasks', function (Blueprint $table) {
            $table->dropColumn(['proof_photo', 'completed_at']);
        });
    }
};