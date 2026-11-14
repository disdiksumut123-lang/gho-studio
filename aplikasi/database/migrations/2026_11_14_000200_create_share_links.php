<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_links', function (Blueprint $t) {
            $t->id();
            $t->string('token', 40)->unique();
            $t->foreignId('training_id')->constrained()->cascadeOnDelete();
            $t->string('level', 20); // pelatihan agenda mapel aktivitas
            $t->unsignedBigInteger('item_id');
            $t->string('label')->nullable();
            $t->timestamp('opens_at')->nullable();
            $t->timestamp('closes_at')->nullable();
            $t->unsignedInteger('opened')->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('share_links'); }
};
