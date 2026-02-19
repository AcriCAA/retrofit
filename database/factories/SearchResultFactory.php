<?php

namespace Database\Factories;

use App\Models\SearchRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class SearchResultFactory extends Factory
{
    public function definition(): array
    {
        $marketplace = fake()->randomElement(['ebay', 'poshmark', 'mercari', 'thredup', 'grailed']);

        return [
            'search_request_id' => SearchRequest::factory(),
            'marketplace' => $marketplace,
            'external_id' => fake()->unique()->numerify('########'),
            'title' => fake()->sentence(6),
            'description' => fake()->optional(0.7)->paragraph(),
            'price' => fake()->randomFloat(2, 5, 500),
            'currency' => 'USD',
            'condition' => fake()->randomElement(['New with tags', 'Like new', 'Good', 'Fair', 'Pre-owned']),
            'seller_name' => fake()->userName(),
            'url' => fake()->url(),
            'image_url' => 'https://picsum.photos/seed/' . fake()->unique()->word() . '/400/400',
            'relevance_score' => fake()->randomFloat(4, 0.5, 1.0),
            'user_status' => fake()->randomElement(['new', 'new', 'new', 'viewed', 'saved', 'dismissed']),
            'is_notified' => fake()->boolean(60),
        ];
    }

    public function new(): static
    {
        return $this->state(fn () => ['user_status' => 'new']);
    }

    public function saved(): static
    {
        return $this->state(fn () => ['user_status' => 'saved']);
    }

    public function onMarketplace(string $marketplace): static
    {
        return $this->state(fn () => ['marketplace' => $marketplace]);
    }
}
