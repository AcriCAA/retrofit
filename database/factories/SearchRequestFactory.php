<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SearchRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'conversation_id' => null,
            'product_category_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(10),
            'image_path' => null,
            'status' => 'active',
            'search_frequency_minutes' => fake()->randomElement([30, 60, 120, 360, 1440]),
            'min_price' => null,
            'max_price' => null,
            'last_searched_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function paused(): static
    {
        return $this->state(fn () => ['status' => 'paused']);
    }

    public function withPriceRange(float $min, float $max): static
    {
        return $this->state(fn () => [
            'min_price' => $min,
            'max_price' => $max,
        ]);
    }

    public function searched(): static
    {
        return $this->state(fn () => [
            'last_searched_at' => now()->subMinutes(fake()->numberBetween(5, 120)),
        ]);
    }
}
