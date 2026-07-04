<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LessonDifficultyEnum;
use App\Enums\LessonProgressStatusEnum;
use App\Enums\LessonSourceTypeEnum;
use App\Enums\LessonStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Thinkycz\LaravelCore\Models\BaseModel;

class Lesson extends BaseModel
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
     * Collection id getter.
     */
    public function getCollectionId(): int
    {
        return $this->assertInt('collection_id');
    }

    /**
     * User id getter.
     */
    public function getUserId(): int
    {
        return $this->assertInt('user_id');
    }

    /**
     * Title getter.
     */
    public function getTitle(): string
    {
        return $this->assertString('title');
    }

    /**
     * Source type getter.
     */
    public function getSourceType(): LessonSourceTypeEnum
    {
        return $this->assertEnum('source_type', LessonSourceTypeEnum::class);
    }

    /**
     * Source text getter.
     */
    public function getSourceText(): string|null
    {
        return $this->assertNullableString('source_text');
    }

    /**
     * Difficulty getter.
     */
    public function getDifficulty(): LessonDifficultyEnum
    {
        return $this->assertEnum('difficulty', LessonDifficultyEnum::class);
    }

    /**
     * Status getter.
     */
    public function getStatus(): LessonStatusEnum
    {
        return $this->assertEnum('status', LessonStatusEnum::class);
    }

    /**
     * Progress status getter.
     */
    public function getProgressStatus(): LessonProgressStatusEnum
    {
        return $this->assertEnum('progress_status', LessonProgressStatusEnum::class);
    }

    /**
     * Quick summary getter.
     *
     * @return array<int, string>|null
     */
    public function getQuickSummary(): array|null
    {
        $summary = $this->assertNullableArray('quick_summary');

        if ($summary === null) {
            return null;
        }

        $normalized = [];
        foreach ($summary as $item) {
            if (\is_string($item)) {
                $normalized[] = $item;
            }
        }

        return $normalized;
    }

    /**
     * Simple explanation getter.
     */
    public function getSimpleExplanation(): string|null
    {
        return $this->assertNullableString('simple_explanation');
    }

    /**
     * Deep explanation getter.
     *
     * @return array<string, mixed>|null
     */
    public function getDeepExplanation(): array|null
    {
        $explanation = $this->assertNullableArray('deep_explanation');

        if ($explanation === null) {
            return null;
        }

        $normalized = [];
        foreach ($explanation as $key => $value) {
            if (\is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * AI raw response getter.
     *
     * @return array<string, mixed>|null
     */
    public function getAiRawResponse(): array|null
    {
        $response = $this->assertNullableArray('ai_raw_response');

        if ($response === null) {
            return null;
        }

        $normalized = [];
        foreach ($response as $key => $value) {
            if (\is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Error message getter.
     */
    public function getErrorMessage(): string|null
    {
        return $this->assertNullableString('error_message');
    }

    /**
     * Completed at getter.
     */
    public function getCompletedAt(): Carbon|null
    {
        return $this->assertNullableCarbon('completed_at');
    }

    /**
     * Created at getter.
     */
    public function getCreatedAt(): Carbon
    {
        return $this->assertCarbon('created_at');
    }

    /**
     * Updated at getter.
     */
    public function getUpdatedAt(): Carbon
    {
        return $this->assertCarbon('updated_at');
    }

    /**
     * Whether the lesson is still being generated.
     */
    public function isGenerating(): bool
    {
        return \in_array($this->getStatus()->value, LessonStatusEnum::activeValues(), true);
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
     * User relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Terms relationship.
     *
     * @return HasMany<Term, $this>
     */
    public function terms(): HasMany
    {
        return $this->hasMany(Term::class, 'lesson_id');
    }

    /**
     * Flashcards relationship.
     *
     * @return HasMany<Flashcard, $this>
     */
    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class, 'lesson_id');
    }

    /**
     * Quizzes relationship.
     *
     * @return HasMany<Quiz, $this>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'lesson_id');
    }

    /**
     * Tutor messages relationship.
     *
     * @return HasMany<TutorMessage, $this>
     */
    public function tutorMessages(): HasMany
    {
        return $this->hasMany(TutorMessage::class, 'lesson_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_type' => LessonSourceTypeEnum::class,
            'difficulty' => LessonDifficultyEnum::class,
            'status' => LessonStatusEnum::class,
            'progress_status' => LessonProgressStatusEnum::class,
            'quick_summary' => 'array',
            'deep_explanation' => 'array',
            'ai_raw_response' => 'array',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
