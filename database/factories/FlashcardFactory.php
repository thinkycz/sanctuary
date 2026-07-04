<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FlashcardDifficultyEnum;
use App\Models\Flashcard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flashcard>
 */
class FlashcardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'collection_id' => CollectionFactory::new(),
            'lesson_id' => null,
            'term_id' => null,
            'front' => $this->faker->word(),
            'back' => $this->faker->word(),
            'example' => $this->faker->optional()->sentence(),
            'difficulty' => $this->faker->randomElement(FlashcardDifficultyEnum::values()),
            'review_count' => $this->faker->numberBetween(0, 10),
            'due_at' => $this->faker->optional()->dateTimeBetween('now', '+4 days'),
            'last_reviewed_at' => $this->faker->optional()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
