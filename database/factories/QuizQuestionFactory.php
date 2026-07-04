<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuizQuestionTypeEnum;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $options = [$this->faker->word(), $this->faker->word(), $this->faker->word(), $this->faker->word()];

        return [
            'quiz_id' => QuizFactory::new(),
            'type' => QuizQuestionTypeEnum::MultipleChoice->value,
            'question' => $this->faker->sentence() . '?',
            'options' => $options,
            'correct_answer' => $this->faker->randomElement($options),
            'explanation' => $this->faker->optional()->sentence(),
            'order' => $this->faker->numberBetween(0, 20),
        ];
    }
}
