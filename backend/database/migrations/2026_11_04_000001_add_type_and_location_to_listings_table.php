<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $t) {
            $t->string('type')->default('Hotel');   // Hotel | Airbnb | Motel | Pension house | Resort
            $t->string('location')->nullable();     // area / barangay in Cagayan de Oro
        });
    }
    public function down(): void
    {
        Schema::table('listings', fn (Blueprint $t) => $t->dropColumn(['type', 'location']));
    }
};
