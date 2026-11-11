<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->foreignId('guest_id')->constrained('users')->cascadeOnDelete();
            $t->dateTime('check_in');
            $t->dateTime('check_out');
            $t->string('status')->default('pending'); // pending | flagged | approved | declined | cancelled
            $t->string('flag_reason')->nullable();    // first_booking | within_24h
            $t->string('access_code', 6)->nullable();
            $t->string('access_status')->nullable();  // active | removed
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
        });
        Schema::create('lock_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->string('event');
            $t->string('result')->default('success'); // success | failed
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('lock_events');
        Schema::dropIfExists('bookings');
    }
};
