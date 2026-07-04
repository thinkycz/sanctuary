<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
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
            'quiz_id' => QuizFactory::new(),
            'score' => $this->faker->numberBetween(0, 100),
            'answers' => [],
            'mistakes' => [],
            'completed_at' => Carbon::now(),
        ];
    }
}
