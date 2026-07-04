<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuizStatusEnum;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
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
            'title' => $this->faker->sentence(3),
            'status' => $this->faker->randomElement(QuizStatusEnum::values()),
            'score' => $this->faker->optional()->numberBetween(0, 100),
            'total_questions' => $this->faker->numberBetween(3, 10),
            'completed_at' => $this->faker->optional()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
