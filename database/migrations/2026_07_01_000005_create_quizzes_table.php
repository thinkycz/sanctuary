<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Thinkycz\LaravelCore\Support\Resolver;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Resolver::resolveSchemaBuilder()->create('quizzes', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('lesson_id')
                ->nullable()
                ->constrained('lessons')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('title');
            $table->string('status')->default('not_started');
            $table->integer('score')->nullable();
            $table->integer('total_questions')->default(0);
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['collection_id', 'status'], 'quizzes_collection_status_idx');
            $table->index(['user_id'], 'quizzes_user_idx');
        });

        Resolver::resolveSchemaBuilder()->create('quiz_questions', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('type')->default('multiple_choice');
            $table->text('question');
            $table->json('options')->nullable();
            $table->text('correct_answer');
            $table->text('explanation')->nullable();
            $table->integer('order')->default(0);

            $table->timestamps();

            $table->index(['quiz_id', 'order'], 'quiz_questions_quiz_order_idx');
        });

        Resolver::resolveSchemaBuilder()->create('quiz_attempts', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->integer('score');
            $table->json('answers');
            $table->json('mistakes')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['quiz_id'], 'quiz_attempts_quiz_idx');
            $table->index(['user_id'], 'quiz_attempts_user_idx');
        });
    }
};
