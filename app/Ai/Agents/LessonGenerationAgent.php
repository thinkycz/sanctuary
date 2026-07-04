<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Thinkycz\LaravelCore\Support\Config;

class LessonGenerationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * The language for explanations and content.
     */
    private string $language = 'en';

    /**
     * The difficulty level.
     */
    private string $difficulty = 'intermediate';

    /**
     * The subject area (e.g. Mathematics, Python Programming).
     */
    private string $subject = '';

    /**
     * Generation options (which sections to produce).
     *
     * @var array<string, bool>
     */
    private array $options = [
        'terms' => true,
        'deep_explanation' => true,
        'flashcards' => true,
        'quiz' => true,
    ];

    /**
     * Set the language for explanations and content.
     */
    public function withLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Set the difficulty.
     */
    public function withDifficulty(string $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    /**
     * Set the subject area.
     */
    public function withSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set generation options.
     *
     * @param array<string, bool> $options
     */
    public function withOptions(array $options): static
    {
        $this->options = [...$this->options, ...$options];

        return $this;
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        $sections = [];

        if ($this->options['terms'] ?? true) {
            $sections[] = 'key terms with definitions';
        }

        if ($this->options['deep_explanation'] ?? true) {
            $sections[] = 'a deep explanation with key concepts, worked examples, notes, and common mistakes';
        }

        if ($this->options['flashcards'] ?? true) {
            $sections[] = 'flashcards';
        }

        if ($this->options['quiz'] ?? true) {
            $sections[] = 'a quiz with multiple-choice questions';
        }

        $sectionList = $sections === [] ? 'a quick summary and simple explanation' : \implode(', ', $sections);

        $subjectLine = $this->subject !== '' ? "\n\nThe subject area is {$this->subject}." : '';

        return "You are an expert teacher and educator. Your task is to analyze the given content and produce a structured lesson for a learner.\n"
            . "The learner's language is {$this->language}. All explanations, definitions, notes, and content must be in {$this->language}.\n"
            . "The learner's level is {$this->difficulty}.{$subjectLine}\n\n"
            . "Generate: a concise title, a quick summary (3-5 bullet points), a simple explanation, and {$sectionList}.\n"
            . "Be accurate, clear, and pedagogically sound. Adapt the content to the subject matter. Avoid overly long text.\n"
            . 'Return ONLY valid JSON matching the provided schema.';
    }

    /**
     * Get the model for the agent.
     */
    public function model(): string
    {
        return Config::inject()->assertString('ai.lesson_generation.model');
    }

    /**
     * Get the provider for the agent.
     */
    public function provider(): string
    {
        return Config::inject()->assertString('ai.lesson_generation.provider');
    }

    /**
     * Get the structured output schema definition.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $stringArray = $schema->array()->items($schema->string());

        $keyConcept = $schema->object([
            'title' => $schema->string(),
            'explanation' => $schema->string(),
            'examples' => $stringArray,
        ]);

        $workedExample = $schema->object([
            'problem' => $schema->string(),
            'solution' => $schema->string(),
            'steps' => $stringArray,
        ]);

        $deepExplanation = $schema->object([
            'key_concepts' => $schema->array()->items($keyConcept),
            'worked_examples' => $schema->array()->items($workedExample),
            'notes' => $stringArray,
            'common_mistakes' => $stringArray,
        ]);

        $termItem = $schema->object([
            'term' => $schema->string(),
            'definition' => $schema->string(),
            'category' => $schema->string(),
            'example' => $schema->string(),
        ]);

        $flashcardItem = $schema->object([
            'front' => $schema->string(),
            'back' => $schema->string(),
            'example' => $schema->string(),
        ]);

        $quizQuestion = $schema->object([
            'type' => $schema->string(),
            'question' => $schema->string(),
            'options' => $stringArray,
            'correct_answer' => $schema->string(),
            'explanation' => $schema->string(),
        ]);

        $quiz = $schema->object([
            'title' => $schema->string(),
            'questions' => $schema->array()->items($quizQuestion),
        ]);

        return [
            'title' => $schema->string(),
            'quick_summary' => $stringArray,
            'simple_explanation' => $schema->string(),
            'deep_explanation' => $deepExplanation,
            'terms' => $schema->array()->items($termItem),
            'flashcards' => $schema->array()->items($flashcardItem),
            'quiz' => $quiz,
        ];
    }
}
