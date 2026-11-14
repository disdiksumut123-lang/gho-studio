<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->json('roles')->nullable();
            $t->string('locale', 5)->default('id');
            $t->string('nip', 30)->nullable();
            $t->string('jabatan')->nullable();
            $t->string('unit_kerja')->nullable();
        });

        Schema::create('ai_providers', function (Blueprint $t) {
            $t->id();
            $t->string('label');
            $t->string('kind', 30); // openai_compatible | gemini | anthropic
            $t->string('base_url')->nullable();
            $t->string('model');
            $t->text('api_key')->nullable(); // terenkripsi
            $t->boolean('enabled')->default(true);
            $t->boolean('is_default')->default(false);
            $t->unsignedInteger('max_tokens')->default(4000);
            $t->timestamps();
        });

        Schema::create('ai_usage', function (Blueprint $t) {
            $t->id();
            $t->foreignId('provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('feature', 60);
            $t->unsignedInteger('tokens_in')->default(0);
            $t->unsignedInteger('tokens_out')->default(0);
            $t->unsignedInteger('latency_ms')->default(0);
            $t->boolean('ok')->default(true);
            $t->string('error')->nullable();
            $t->timestamps();
        });

        Schema::create('trainings', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('type', 20)->default('LATSAR'); // LATSAR PKP PKA PKN2 LAIN
            $t->boolean('use_cohorts')->default(false);
            $t->string('join_code', 20)->unique();
            $t->dateTime('opens_at')->nullable();
            $t->dateTime('closes_at')->nullable();
            $t->boolean('sequential')->default(true); // agenda berurutan
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('cohorts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('training_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('join_code', 20)->unique();
            $t->timestamps();
        });

        Schema::create('enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('training_id')->constrained()->cascadeOnDelete();
            $t->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('role', 20)->default('peserta'); // peserta coach fasilitator
            $t->timestamps();
            $t->unique(['training_id', 'user_id', 'role']);
        });

        Schema::create('agendas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('training_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('position')->default(0);
            $t->dateTime('opens_at')->nullable();
            $t->dateTime('closes_at')->nullable();
            $t->boolean('sequential')->default(true); // wajib setelah agenda sebelumnya
            $t->timestamps();
        });

        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agenda_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('position')->default(0);
            $t->dateTime('opens_at')->nullable();
            $t->dateTime('closes_at')->nullable();
            $t->boolean('sequential')->default(true);
            $t->timestamps();
        });

        Schema::create('activities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $t->string('owner_role', 20)->default('fasilitator');
            $t->string('type', 30);
            $t->string('title');
            $t->longText('instructions')->nullable();
            $t->json('config')->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->dateTime('opens_at')->nullable();
            $t->dateTime('closes_at')->nullable();
            $t->boolean('sequential')->default(true);
            $t->boolean('required')->default(true);
            $t->boolean('allow_upload')->default(false);
            $t->timestamps();
        });

        Schema::create('answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('item_key', 60)->default('main');
            $t->longText('body')->nullable();
            $t->longText('body_text')->nullable();
            $t->string('file_path')->nullable();
            $t->string('file_name')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->boolean('submitted')->default(false);
            $t->timestamps();
            $t->index(['activity_id', 'user_id', 'item_key', 'version']);
        });

        Schema::create('feedbacks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('answer_id')->constrained()->cascadeOnDelete();
            $t->longText('content')->nullable();
            $t->json('scores')->nullable();
            $t->unsignedInteger('total')->nullable();
            $t->string('status', 20)->default('auto'); // auto approved returned
            $t->longText('reviewer_note')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('completions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamp('completed_at');
            $t->unique(['activity_id', 'user_id']);
        });

        Schema::create('case_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('round')->default(0);
            $t->string('status', 20)->default('berjalan');
            $t->json('final')->nullable();
            $t->timestamps();
        });

        Schema::create('case_turns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('case_session_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('round')->default(0);
            $t->string('role', 20); // opening participant feedback condition final
            $t->longText('content');
            $t->json('meta')->nullable();
            $t->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('position')->default(0);
            $t->string('type', 20)->default('mcq');
            $t->text('prompt');
            $t->json('data')->nullable();
            $t->json('answer')->nullable();
            $t->text('explanation')->nullable();
            $t->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->json('answers')->nullable();
            $t->unsignedInteger('score')->default(0);
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
        });

        Schema::create('media_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('max_position')->default(0);
            $t->unsignedInteger('duration')->default(0);
            $t->json('pages_seen')->nullable();
            $t->unsignedInteger('active_seconds')->default(0);
            $t->timestamps();
            $t->unique(['activity_id', 'user_id']);
        });
    }

    public function down(): void
    {
        foreach (['media_progress', 'quiz_attempts', 'quiz_questions', 'case_turns', 'case_sessions', 'completions', 'feedbacks', 'answers', 'activities', 'subjects', 'agendas', 'enrollments', 'cohorts', 'trainings', 'ai_usage', 'ai_providers'] as $tb) {
            Schema::dropIfExists($tb);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['roles', 'locale', 'nip', 'jabatan', 'unit_kerja']));
    }
};
