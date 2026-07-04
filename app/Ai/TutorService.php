<?php

declare(strict_types=1);

namespace App\Ai;

use App\Ai\Agents\TutorAgent;
use App\Enums\TutorMessageRoleEnum;
use App\Models\Collection;
use App\Models\Lesson;
use App\Models\TutorMessage;
use App\Models\User;
use Thinkycz\LaravelCore\Support\Typer;
use Throwable;

class TutorService
{
    /**
     * Send a user message to the collection-level tutor and persist the exchange.
     */
    public function sendCollectionMessage(Collection $collection, User $user, string $content): TutorMessage
    {
        $context = $this->collectionContext($collection);

        return $this->sendMessage($collection, $user, $content, $context, null);
    }

    /**
     * Send a user message to the lesson-level tutor and persist the exchange.
     */
    public function sendLessonMessage(Collection $collection, Lesson $lesson, User $user, string $content): TutorMessage
    {
        $context = $this->lessonContext($collection, $lesson);

        return $this->sendMessage($collection, $user, $content, $context, $lesson->getId());
    }

    /**
     * Build the context string for a collection-level tutor session.
     */
    private function collectionContext(Collection $collection): string
    {
        $parts = [
            "Collection: {$collection->getTitle()}",
        ];

        if ($collection->getSubject() !== null) {
            $parts[] = "Subject: {$collection->getSubject()}";
        }

        if ($collection->getDescription() !== null) {
            $parts[] = "Description: {$collection->getDescription()}";
        }

        return \implode("\n", $parts);
    }

    /**
     * Build the context string for a lesson-level tutor session.
     */
    private function lessonContext(Collection $collection, Lesson $lesson): string
    {
        $parts = [$this->collectionContext($collection)];
        $parts[] = "Lesson: {$lesson->getTitle()}";
        $parts[] = "Difficulty: {$lesson->getDifficulty()->value}";

        if ($lesson->getSimpleExplanation() !== null) {
            $parts[] = "Simple explanation: {$lesson->getSimpleExplanation()}";
        }

        return \implode("\n", $parts);
    }

    /**
     * Persist the user message, call the AI, and persist the assistant response.
     */
    private function sendMessage(Collection $collection, User $user, string $content, string $context, int|null $lessonId): TutorMessage
    {
        $userMessage = TutorMessage::create([
            'user_id' => $user->getKey(),
            'collection_id' => $collection->getId(),
            'lesson_id' => $lessonId,
            'role' => TutorMessageRoleEnum::User->value,
            'content' => $content,
        ]);

        try {
            $agent = TutorAgent::make()->withContext($context);
            $response = $agent->prompt($content);
            $assistantContent = Typer::assertString($response->text);
        } catch (Throwable $e) {
            $assistantContent = 'Sorry, I could not generate a response right now. Please try again.';
        }

        return TutorMessage::create([
            'user_id' => $user->getKey(),
            'collection_id' => $collection->getId(),
            'lesson_id' => $lessonId,
            'role' => TutorMessageRoleEnum::Assistant->value,
            'content' => $assistantContent,
        ]);
    }
}
