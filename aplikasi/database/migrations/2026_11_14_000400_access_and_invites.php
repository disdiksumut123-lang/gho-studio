<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $t) {
            $t->string('access_mode', 12)->default('setujui'); // terbuka setujui undangan
        });
        Schema::table('users', function (Blueprint $t) {
            $t->string('whatsapp', 20)->nullable();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key', 60)->primary();
            $t->text('value')->nullable();
        });
        Schema::table('enrollments', function (Blueprint $t) {
            $t->string('status', 12)->default('aktif'); // aktif menunggu ditolak
        });
        Schema::create('invites', function (Blueprint $t) {
            $t->id();
            $t->foreignId('training_id')->constrained()->cascadeOnDelete();
            $t->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name')->nullable();
            $t->string('nip', 30)->nullable();
            $t->string('email')->nullable();
            $t->string('whatsapp', 20)->nullable();
            $t->string('token', 24)->unique();
            $t->timestamp('sent_at')->nullable();
            $t->string('jabatan')->nullable();
            $t->string('unit_kerja')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // terisi saat dipakai
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invites');
        Schema::dropIfExists('settings');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('whatsapp'));
        Schema::table('enrollments', fn (Blueprint $t) => $t->dropColumn('status'));
        Schema::table('trainings', fn (Blueprint $t) => $t->dropColumn('access_mode'));
    }
};
