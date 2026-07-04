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
        Resolver::resolveSchemaBuilder()->create('flashcards', static function (Blueprint $table): void {
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

            $table->foreignId('term_id')
                ->nullable()
                ->constrained('terms')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('front');
            $table->string('back');
            $table->text('example')->nullable();
            $table->string('difficulty')->default('again');
            $table->integer('review_count')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['collection_id', 'due_at'], 'flashcards_collection_due_idx');
            $table->index(['user_id'], 'flashcards_user_idx');
        });
    }
};
