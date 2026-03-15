<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'email_verified_at' => now(),
            'status' => 1,
        ];
    }
    
    /**
     * Indicate that the user should have a profile with full_name.
     */
    public function withProfile(): static
    {
        return $this->afterCreating(function (\App\Models\User $user) {
            \App\Models\UserProfile::create([
                'user_id' => $user->id,
                'full_name' => fake()->name(),
            ]);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
