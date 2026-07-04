<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LessonStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Thinkycz\LaravelCore\Models\BaseModel;

class Collection extends BaseModel
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
     * Title getter.
     */
    public function getTitle(): string
    {
        return $this->assertString('title');
    }

    /**
     * Description getter.
     */
    public function getDescription(): string|null
    {
        return $this->assertNullableString('description');
    }

    /**
     * Icon getter.
     */
    public function getIcon(): string|null
    {
        return $this->assertNullableString('icon');
    }

    /**
     * Subject getter.
     */
    public function getSubject(): string|null
    {
        return $this->assertNullableString('subject');
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
     * User relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Lessons relationship.
     *
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'collection_id');
    }

    /**
     * Terms relationship.
     *
     * @return HasMany<Term, $this>
     */
    public function terms(): HasMany
    {
        return $this->hasMany(Term::class, 'collection_id');
    }

    /**
     * Flashcards relationship.
     *
     * @return HasMany<Flashcard, $this>
     */
    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class, 'collection_id');
    }

    /**
     * Quizzes relationship.
     *
     * @return HasMany<Quiz, $this>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'collection_id');
    }

    /**
     * Tutor messages relationship.
     *
     * @return HasMany<TutorMessage, $this>
     */
    public function tutorMessages(): HasMany
    {
        return $this->hasMany(TutorMessage::class, 'collection_id');
    }

    /**
     * Ready lessons for this collection.
     *
     * @return EloquentCollection<int, Lesson>
     */
    public function readyLessons(): EloquentCollection
    {
        return $this->lessons()
            ->where('status', LessonStatusEnum::Ready->value)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
