<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('price');
            $t->date('available_from')->nullable();
            $t->date('available_to')->nullable();
            $t->string('status')->default('active'); // active | paused
            $t->unsignedTinyInteger('battery')->default(100); // smart lock battery %
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('listings'); }
};
