<?php

namespace Database\Factories;

use App\Models\SearchRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class SearchRequestAttributeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'search_request_id' => SearchRequest::factory(),
            'key' => fake()->randomElement(['brand', 'size', 'color', 'fit', 'material']),
            'value' => fake()->word(),
        ];
    }
}
