<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TutorMessageRoleEnum;
use App\Models\TutorMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TutorMessage>
 */
class TutorMessageFactory extends Factory
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
            'role' => $this->faker->randomElement(TutorMessageRoleEnum::values()),
            'content' => $this->faker->paragraph(),
        ];
    }
}
