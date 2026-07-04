<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TermDifficultyEnum;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
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
            'term' => $this->faker->word(),
            'definition' => $this->faker->sentence(),
            'category' => $this->faker->optional()->randomElement(['concept', 'formula', 'term', 'rule', 'definition']),
            'example' => $this->faker->optional()->sentence(),
            'difficulty' => $this->faker->randomElement(TermDifficultyEnum::values()),
        ];
    }
}
