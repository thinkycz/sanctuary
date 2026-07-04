<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuizStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Thinkycz\LaravelCore\Models\BaseModel;

class Quiz extends BaseModel
{
    /**
     * Base select query.
     *
     * @param Builder<static> $builder
     */
    public static function querySelect(Builder $builder): void
    {
        $builder->getQuery()->select($builder->qualifyColumn('*'));
    }

    /**
     * Search scope.
     *
     * @param Builder<static> $builder
     */
    public static function scopeSearch(Builder $builder, string $search): void
    {
        $builder->getQuery()->where($builder->qualifyColumn('title'), 'LIKE', "%{$search}%");
    }

    /**
     * Id getter.
     */
    public function getId(): int
    {
        return $this->assertInt('id');
    }

    /**
     * User id getter.
     */
    public function getUserId(): int
    {
        return $this->assertInt('user_id');
    }

    /**
     * Collection id getter.
     */
    public function getCollectionId(): int
    {
        return $this->assertInt('collection_id');
    }

    /**
     * Lesson id getter.
     */
    public function getLessonId(): int|null
    {
        return $this->assertNullableInt('lesson_id');
    }

    /**
     * Title getter.
     */
    public function getTitle(): string
    {
        return $this->assertString('title');
    }

    /**
     * Status getter.
     */
    public function getStatus(): QuizStatusEnum
    {
        return $this->assertEnum('status', QuizStatusEnum::class);
    }

    /**
     * Score getter.
     */
    public function getScore(): int|null
    {
        return $this->assertNullableInt('score');
    }

    /**
     * Total questions getter.
     */
    public function getTotalQuestions(): int
    {
        return $this->assertInt('total_questions');
    }

    /**
     * Completed at getter.
     */
    public function getCompletedAt(): Carbon|null
    {
        return $this->assertNullableCarbon('completed_at');
    }

    /**
     * User relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Collection relationship.
     *
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    /**
     * Lesson relationship.
     *
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    /**
     * Questions relationship.
     *
     * @return HasMany<QuizQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'quiz_id')->orderBy('order');
    }

    /**
     * Attempts relationship.
     *
     * @return HasMany<QuizAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'quiz_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuizStatusEnum::class,
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
