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
        Resolver::resolveSchemaBuilder()->create('collections', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('subject')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'updated_at'], 'collections_user_updated_idx');
        });
    }
};
