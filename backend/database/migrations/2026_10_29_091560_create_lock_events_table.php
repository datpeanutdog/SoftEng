<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lock_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->string('event');
            $t->string('result')->default('success'); // success | failed
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('lock_events'); }
};
