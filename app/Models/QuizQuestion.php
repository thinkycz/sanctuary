<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuizQuestionTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Thinkycz\LaravelCore\Models\BaseModel;

class QuizQuestion extends BaseModel
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
        $builder->getQuery()->where($builder->qualifyColumn('question'), 'LIKE', "%{$search}%");
    }

    /**
     * Id getter.
     */
    public function getId(): int
    {
        return $this->assertInt('id');
    }

    /**
     * Quiz id getter.
     */
    public function getQuizId(): int
    {
        return $this->assertInt('quiz_id');
    }

    /**
     * Type getter.
     */
    public function getType(): QuizQuestionTypeEnum
    {
        return $this->assertEnum('type', QuizQuestionTypeEnum::class);
    }

    /**
     * Question getter.
     */
    public function getQuestion(): string
    {
        return $this->assertString('question');
    }

    /**
     * Options getter.
     *
     * @return array<int, string>|null
     */
    public function getOptions(): array|null
    {
        $options = $this->assertNullableArray('options');

        if ($options === null) {
            return null;
        }

        $normalized = [];
        foreach ($options as $option) {
            if (\is_string($option)) {
                $normalized[] = $option;
            }
        }

        return $normalized;
    }

    /**
     * Correct answer getter.
     */
    public function getCorrectAnswer(): string
    {
        return $this->assertString('correct_answer');
    }

    /**
     * Explanation getter.
     */
    public function getExplanation(): string|null
    {
        return $this->assertNullableString('explanation');
    }

    /**
     * Order getter.
     */
    public function getOrder(): int
    {
        return $this->assertInt('order');
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
            'type' => QuizQuestionTypeEnum::class,
            'options' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
