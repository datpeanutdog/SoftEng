<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('guest_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('host_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->string('listing_title');
            $t->string('guest_name');
            $t->unsignedInteger('price');
            $t->string('code', 6);                         // 6-digit booking / door code
            $t->string('status')->default('Pending');     // Pending | Flagged | Approved | Denied | Cancelled
            $t->boolean('go_enabled')->default(false);
            $t->dateTime('check_in');
            $t->dateTime('check_out');
            $t->string('flag_reason')->nullable();         // first_booking | within_24h
            $t->string('access_status')->nullable();       // active | removed
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('bookings'); }
};
