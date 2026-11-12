<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $t) {
            $t->unsignedTinyInteger('star_rating')->nullable();
            $t->boolean('serves_breakfast')->default(false);
            $t->boolean('has_pool')->default(false);
            $t->boolean('featured')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('listings', fn (Blueprint $t) => $t->dropColumn([
            'star_rating', 'serves_breakfast', 'has_pool', 'featured',
        ]));
    }
};
