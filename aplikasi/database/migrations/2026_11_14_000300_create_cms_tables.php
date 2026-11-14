<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('platform', 20);
            $t->string('name');
            $t->string('external_id')->nullable();
            $t->text('token')->nullable();      // terenkripsi
            $t->text('refresh_token')->nullable(); // terenkripsi
            $t->timestamp('expires_at')->nullable();
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });
        Schema::create('social_posts', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->longText('caption')->nullable();
            $t->json('captions')->nullable();   // per platform
            $t->json('platforms')->nullable();
            $t->string('media_path')->nullable();
            $t->string('media_name')->nullable();
            $t->string('media_type', 10)->nullable(); // video gambar
            $t->timestamp('scheduled_at')->nullable();
            $t->string('status', 20)->default('draf'); // draf terjadwal diproses selesai sebagian
            $t->json('results')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('social_accounts');
    }
};
