<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TermDifficultyEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Thinkycz\LaravelCore\Models\BaseModel;

class Term extends BaseModel
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
            $inner->where($builder->qualifyColumn('term'), 'LIKE', "%{$search}%")
                ->orWhere($builder->qualifyColumn('definition'), 'LIKE', "%{$search}%");
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
     * Term getter.
     */
    public function getTerm(): string
    {
        return $this->assertString('term');
    }

    /**
     * Definition getter.
     */
    public function getDefinition(): string
    {
        return $this->assertString('definition');
    }

    /**
     * Category getter.
     */
    public function getCategory(): string|null
    {
        return $this->assertNullableString('category');
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
    public function getDifficulty(): TermDifficultyEnum
    {
        return $this->assertEnum('difficulty', TermDifficultyEnum::class);
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'difficulty' => TermDifficultyEnum::class,
            'last_reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
