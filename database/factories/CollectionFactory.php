<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = ['Calculus Basics', 'Python Programming', 'World History', 'Organic Chemistry', 'Spanish Grammar', 'Data Structures'];

        return [
            'user_id' => UserFactory::new(),
            'title' => $this->faker->unique()->randomElement($titles),
            'description' => $this->faker->optional()->sentence(),
            'icon' => $this->faker->optional()->randomElement(['📚', '🔬', '💻', '🌍', '🧮', '⚡']),
            'subject' => $this->faker->optional()->randomElement(['Mathematics', 'Computer Science', 'History', 'Chemistry', 'Languages', 'Engineering']),
        ];
    }
}
