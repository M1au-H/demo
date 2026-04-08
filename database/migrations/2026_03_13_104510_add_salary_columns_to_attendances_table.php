<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('late_deduction_amount', 12, 2)->default(0)->after('late_minutes');
            $table->integer('early_leave_minutes')->default(0)->after('checkout_status');
            $table->decimal('early_leave_deduction_amount', 12, 2)->default(0)->after('early_leave_minutes');
            $table->decimal('overtime_pay_amount', 12, 2)->default(0)->after('overtime_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'late_deduction_amount',
                'early_leave_minutes',
                'early_leave_deduction_amount',
                'overtime_pay_amount',
            ]);
        });
    }
};