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
        Resolver::resolveSchemaBuilder()->create('terms', static function (Blueprint $table): void {
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

            $table->string('term');
            $table->string('definition');
            $table->string('category')->nullable();
            $table->text('example')->nullable();
            $table->string('difficulty')->default('unknown');
            $table->timestamp('last_reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['collection_id', 'difficulty'], 'terms_collection_difficulty_idx');
            $table->index(['user_id'], 'terms_user_idx');
        });
    }
};
