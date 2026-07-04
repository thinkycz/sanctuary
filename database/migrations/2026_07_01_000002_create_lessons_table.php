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
        Resolver::resolveSchemaBuilder()->create('lessons', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('source_type')->default('text');
            $table->longText('source_text')->nullable();
            $table->string('difficulty')->default('intermediate');
            $table->string('status')->default('pending');
            $table->string('progress_status')->default('new');
            $table->json('quick_summary')->nullable();
            $table->longText('simple_explanation')->nullable();
            $table->json('deep_explanation')->nullable();
            $table->json('ai_raw_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['collection_id', 'status'], 'lessons_collection_status_idx');
            $table->index(['user_id', 'status'], 'lessons_user_status_idx');
        });
    }
};
