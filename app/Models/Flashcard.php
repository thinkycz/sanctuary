<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FlashcardDifficultyEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Thinkycz\LaravelCore\Models\BaseModel;

class Flashcard extends BaseModel
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
        $builder->getQuery()->where(static function (Builder $inner) use ($builder, $search): void {
            $inner->where($builder->qualifyColumn('front'), 'LIKE', "%{$search}%")
                ->orWhere($builder->qualifyColumn('back'), 'LIKE', "%{$search}%");
        });
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
     * Term id getter.
     */
    public function getTermId(): int|null
    {
        return $this->assertNullableInt('term_id');
    }

    /**
     * Front getter.
     */
    public function getFront(): string
    {
        return $this->assertString('front');
    }

    /**
     * Back getter.
     */
    public function getBack(): string
    {
        return $this->assertString('back');
    }

    /**
     * Example getter.
     */
    public function getExample(): string|null
    {
        return $this->assertNullableString('example');
    }

    /**
     * Difficulty getter.
     */
    public function getDifficulty(): FlashcardDifficultyEnum
    {
        return $this->assertEnum('difficulty', FlashcardDifficultyEnum::class);
    }

    /**
     * Review count getter.
     */
    public function getReviewCount(): int
    {
        return $this->assertInt('review_count');
    }

    /**
     * Due at getter.
     */
    public function getDueAt(): Carbon|null
    {
        return $this->assertNullableCarbon('due_at');
    }

    /**
     * Last reviewed at getter.
     */
    public function getLastReviewedAt(): Carbon|null
    {
        return $this->assertNullableCarbon('last_reviewed_at');
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
     * Term relationship.
     *
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'difficulty' => FlashcardDifficultyEnum::class,
            'due_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
