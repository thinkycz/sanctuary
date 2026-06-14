<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Thinkycz\LaravelCore\Support\Config;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    public static string|null $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => Carbon::now(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
            'locale' => Config::inject()->assertString('app.locale'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state([
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create model with password filled.
     *
     * Stores the plaintext password so the model's `hashed` cast
     * performs the single bcrypt pass. Pre-hashing here would let the
     * cast re-hash the already-hashed value, producing an unusable
     * credential.
     */
    public function password(string|null $password = null): static
    {
        return $this->set('password', $password ?? static::$password ?? 'password');
    }
}
