<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company' => fake()->company(),
            'position' => fake()->jobTitle(),
            'url' => fake()->optional()->url(),
            'location' => fake()->optional()->city(),
            'status' => ApplicationStatus::Wishlist,
            'applied_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
