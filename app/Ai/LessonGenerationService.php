<?php

declare(strict_types=1);

namespace App\Ai;

use App\Ai\Agents\LessonGenerationAgent;
use App\Enums\LessonDifficultyEnum;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class LessonGenerationService
{
    /**
     * Generate a structured lesson from source content.
     *
     * @param array<string, bool> $options
     */
    public function generate(
        string $sourceText,
        string $language,
        LessonDifficultyEnum $difficulty,
        array $options = [],
        string $subject = '',
    ): LessonGenerationResult {
        $agent = $this->buildAgent($sourceText, $language, $difficulty, $options, $subject);

        try {
            $response = $agent->prompt($sourceText);
        } catch (Throwable $e) {
            throw LessonGenerationFailedException::fromThrowable($e);
        }

        $data = $this->extractStructuredData($response);

        if (!$this->isValid($data)) {
            // Retry once with a fresh agent instance.
            try {
                $retryResponse = $this->buildAgent($sourceText, $language, $difficulty, $options, $subject)->prompt($sourceText);
                $data = $this->extractStructuredData($retryResponse);
            } catch (Throwable $e) {
                throw LessonGenerationFailedException::fromThrowable($e);
            }

            if (!$this->isValid($data)) {
                throw LessonGenerationFailedException::invalidJson();
            }
        }

        return LessonGenerationResult::fromArray($data);
    }

    /**
     * Build a configured lesson generation agent.
     *
     * @param array<string, bool> $options
     */
    private function buildAgent(
        string $sourceText,
        string $language,
        LessonDifficultyEnum $difficulty,
        array $options,
        string $subject,
    ): LessonGenerationAgent {
        return LessonGenerationAgent::make()
            ->withLanguage($language)
            ->withDifficulty($difficulty->value)
            ->withSubject($subject)
            ->withOptions($options);
    }

    /**
     * Extract the structured data from the agent response.
     *
     * @return array<string, mixed>
     */
    private function extractStructuredData(\Laravel\Ai\Responses\AgentResponse|StructuredAgentResponse $response): array
    {
        if ($response instanceof StructuredAgentResponse) {
            $array = $response->toArray();

            return $this->normalizeArray($array);
        }

        // Fallback: try to decode the text response as JSON.
        $text = $response->text;
        $decoded = \json_decode($text, true);

        return $this->normalizeArray($decoded);
    }

    /**
     * Normalize a value to a string-keyed array.
     *
     * @return array<string, mixed>
     */
    private function normalizeArray(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if (\is_string($key)) {
                $normalized[$key] = $item;
            }
        }

        return $normalized;
    }

    /**
     * Validate the structured data has the minimum required fields.
     *
     * @param array<string, mixed> $data
     */
    private function isValid(array $data): bool
    {
        return isset($data['title']) &&
            \is_string($data['title']) &&
            $data['title'] !== '' &&
            isset($data['simple_explanation']) &&
            \is_string($data['simple_explanation']);
    }
}
