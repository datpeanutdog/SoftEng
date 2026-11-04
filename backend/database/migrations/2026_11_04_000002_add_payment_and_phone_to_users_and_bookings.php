<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 16)->nullable();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('nights')->default(1);
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->string('refund_status')->default('none');
            $table->string('decision_sms_status')->default('not_sent');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['nights', 'payment_method', 'payment_reference', 'payment_status', 'refund_status', 'decision_sms_status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
