<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_scores', function (Blueprint $table) {
            $table->decimal('admin_rating', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kpi_scores', function (Blueprint $table) {
            $table->decimal('admin_rating', 5, 2)->nullable(false)->change();
        });
    }
};