<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LessonDifficultyEnum;
use App\Enums\LessonProgressStatusEnum;
use App\Enums\LessonSourceTypeEnum;
use App\Enums\LessonStatusEnum;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection_id' => CollectionFactory::new(),
            'user_id' => UserFactory::new(),
            'title' => $this->faker->sentence(4),
            'source_type' => LessonSourceTypeEnum::Text->value,
            'source_text' => $this->faker->paragraph(),
            'difficulty' => $this->faker->randomElement(LessonDifficultyEnum::values()),
            'status' => LessonStatusEnum::Ready->value,
            'progress_status' => LessonProgressStatusEnum::New->value,
            'quick_summary' => [$this->faker->sentence(), $this->faker->sentence(), $this->faker->sentence()],
            'simple_explanation' => $this->faker->paragraphs(3, true),
            'deep_explanation' => [
                'key_concepts' => [],
                'worked_examples' => [],
                'notes' => [],
                'common_mistakes' => [],
            ],
            'ai_raw_response' => null,
            'error_message' => null,
            'completed_at' => Carbon::now(),
        ];
    }

    /**
     * Mark the lesson as generating.
     */
    public function generating(): static
    {
        return $this->state([
            'status' => LessonStatusEnum::Generating->value,
            'completed_at' => null,
            'quick_summary' => null,
            'simple_explanation' => null,
            'deep_explanation' => null,
        ]);
    }

    /**
     * Mark the lesson as failed.
     */
    public function failed(): static
    {
        return $this->state([
            'status' => LessonStatusEnum::Failed->value,
            'completed_at' => null,
            'quick_summary' => null,
            'simple_explanation' => null,
            'deep_explanation' => null,
            'error_message' => 'The AI service could not generate the lesson. Please try again.',
        ]);
    }
}
