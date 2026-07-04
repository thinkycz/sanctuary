<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Thinkycz\LaravelCore\Models\BaseModel;

class QuizAttempt extends BaseModel
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
        $builder->getQuery()->where($builder->qualifyColumn('score'), 'LIKE', "%{$search}%");
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
     * Quiz id getter.
     */
    public function getQuizId(): int
    {
        return $this->assertInt('quiz_id');
    }

    /**
     * Score getter.
     */
    public function getScore(): int
    {
        return $this->assertInt('score');
    }

    /**
     * Answers getter.
     *
     * @return array<string, mixed>
     */
    public function getAnswers(): array
    {
        $answers = $this->assertArray('answers');

        $normalized = [];
        foreach ($answers as $key => $value) {
            if (\is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Mistakes getter.
     *
     * @return array<mixed>|null
     */
    public function getMistakes(): array|null
    {
        return $this->assertNullableArray('mistakes');
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
     * Quiz relationship.
     *
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'mistakes' => 'array',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
