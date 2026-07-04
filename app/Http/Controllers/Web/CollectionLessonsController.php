<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\CollectionRepository;
use App\Enums\LessonDifficultyEnum;
use App\Enums\LessonSourceTypeEnum;
use App\Enums\LessonStatusEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Jobs\GenerateLessonJob;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;
use Thinkycz\LaravelCore\Support\Typer;

class CollectionLessonsController
{
    use ValidatesWebRequests;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly CollectionRepository $collections,
    ) {}

    /**
     * Show the lessons list for a collection.
     */
    public function index(int $id): RedirectResponse|Response
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        return Inertia::render('Collections/Lessons', [
            'collection' => $this->collections->serializeCollection($collection),
            'lessons' => $this->collections->lessonsForList($collection),
        ]);
    }

    /**
     * Store a new lesson and dispatch the generation job.
     */
    public function store(Request $request, int $id): RedirectResponse
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $validated = $this->validateRequest($request, [
            'title' => 'nullable|string|max:200',
            'source_text' => 'required|string|min:1|max:20000',
            'difficulty' => 'nullable|string|in:' . \implode(',', LessonDifficultyEnum::values()),
        ]);

        $sourceText = $validated->assertString('source_text');

        if (\mb_trim($sourceText) === '') {
            Thrower::default()->message('source_text', Typer::assertString(\__('lessons.source_text_required')))->throw();
        }

        $difficulty = $this->resolveDifficulty($validated->parseNullableString('difficulty'));
        $title = $validated->assertNullableString('title') ?? \mb_trim(\str_word_count($sourceText, 1)[0] ?? 'New Lesson');

        $lesson = Lesson::create([
            'user_id' => $user->getKey(),
            'collection_id' => $collection->getId(),
            'title' => $title,
            'source_type' => LessonSourceTypeEnum::Text->value,
            'source_text' => $sourceText,
            'difficulty' => $difficulty->value,
            'status' => LessonStatusEnum::Pending->value,
        ]);

        Resolver::resolveQueueingDispatcher()->dispatch(new GenerateLessonJob($lesson->getId()));

        Inertia::flash('success', \__('lessons.generation_started'));

        return Resolver::resolveRedirector()->to('/lessons/' . $lesson->getId());
    }

    /**
     * Resolve a difficulty enum from a nullable string.
     */
    private function resolveDifficulty(string|null $value): LessonDifficultyEnum
    {
        if ($value === null || $value === '') {
            return LessonDifficultyEnum::Intermediate;
        }

        $enum = LessonDifficultyEnum::tryFrom($value);

        return $enum ?? LessonDifficultyEnum::Intermediate;
    }
}
