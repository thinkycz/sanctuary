<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\CollectionRepository;
use App\Enums\FlashcardDifficultyEnum;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;
use Thinkycz\LaravelCore\Support\Typer;

class CollectionFlashcardsController
{
    use ValidatesWebRequests;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly CollectionRepository $collections,
    ) {}

    /**
     * Show the flashcards tab for a collection.
     */
    public function index(int $id): RedirectResponse|Response
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        return Inertia::render('Collections/Flashcards', [
            'collection' => $this->collections->serializeCollection($collection),
            'flashcards' => $this->collections->flashcardsForCollection($collection),
        ]);
    }

    /**
     * Review a flashcard (update difficulty, review count, due date).
     */
    public function review(Request $request, int $id, int $cardId): RedirectResponse
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $flashcard = $this->collections->findOwnedFlashcard($cardId, $user);

        if ($flashcard === null || $flashcard->getCollectionId() !== $collection->getId()) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $validated = $this->validateRequest($request, [
            'difficulty' => 'required|string|in:' . \implode(',', FlashcardDifficultyEnum::values()),
        ]);

        $difficulty = $validated->assertString('difficulty');

        $enum = FlashcardDifficultyEnum::tryFrom($difficulty);

        if ($enum === null) {
            Thrower::default()->message('difficulty', Typer::assertString(\__('flashcards.invalid_difficulty')))->throw();
        }

        $reviewCount = $flashcard->getReviewCount() + 1;
        $dueAt = $this->computeDueAt($enum);

        $flashcard->update([
            'difficulty' => $difficulty,
            'review_count' => $reviewCount,
            'due_at' => $dueAt,
            'last_reviewed_at' => \now(),
        ]);

        return Resolver::resolveRedirector()->back();
    }

    /**
     * Compute the next due date based on difficulty.
     */
    private function computeDueAt(FlashcardDifficultyEnum $difficulty): \Illuminate\Support\Carbon
    {
        return match ($difficulty) {
            FlashcardDifficultyEnum::Again => \now()->addMinutes(10),
            FlashcardDifficultyEnum::Hard => \now()->addHours(4),
            FlashcardDifficultyEnum::Easy => \now()->addDays(2),
        };
    }
}
