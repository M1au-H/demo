<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['user_id', 'date'],    'att_user_date');
            $table->index(['date', 'status'],     'att_date_status');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->index(['user_id', 'date'],          'lv_user_date');
            $table->index(['date', 'type', 'status'],   'lv_date_type_status');
            $table->index(['user_id', 'type', 'status'],'lv_user_type_status');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->index(['user_id', 'month', 'year'], 'py_user_month_year');
            $table->index(['month', 'year', 'status'],  'py_month_year_status');
        });

        Schema::table('kpi_scores', function (Blueprint $table) {
            $table->index(['user_id', 'month', 'year'], 'kpi_user_month_year');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('att_user_date');
            $table->dropIndex('att_date_status');
        });
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex('lv_user_date');
            $table->dropIndex('lv_date_type_status');
            $table->dropIndex('lv_user_type_status');
        });
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('py_user_month_year');
            $table->dropIndex('py_month_year_status');
        });
        Schema::table('kpi_scores', function (Blueprint $table) {
            $table->dropIndex('kpi_user_month_year');
        });
    }
};