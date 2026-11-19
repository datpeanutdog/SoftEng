<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $t) {
            // file name WITHOUT extension, e.g. "limketkai-luxe-hotel" (file lives in public/images/listings/)
            $t->string('photo')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('listings', fn (Blueprint $t) => $t->dropColumn('photo'));
    }
};
