<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_templates', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('training_type', 10)->nullable();
            $t->json('config');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('answer_id')->nullable()->constrained()->nullOnDelete();
            $t->json('sections')->nullable(); // {key: html}
            $t->string('status', 20)->default('draf'); // draf ditinjau disetujui dikembalikan
            $t->text('reviewer_note')->nullable();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['activity_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('form_templates');
    }
};
