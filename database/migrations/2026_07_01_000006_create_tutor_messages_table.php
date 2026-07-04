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
        Resolver::resolveSchemaBuilder()->create('tutor_messages', static function (Blueprint $table): void {
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

            $table->string('role');
            $table->longText('content');

            $table->timestamps();

            $table->index(['collection_id', 'created_at'], 'tutor_messages_collection_created_idx');
            $table->index(['lesson_id', 'created_at'], 'tutor_messages_lesson_created_idx');
            $table->index(['user_id'], 'tutor_messages_user_idx');
        });
    }
};
