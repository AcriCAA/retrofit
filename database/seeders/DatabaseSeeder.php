<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        ProductCategory::firstOrCreate(
            ['slug' => 'clothing'],
            [
                'name' => 'Clothing',
                'description' => 'Discontinued or hard-to-find clothing items',
                'default_attributes' => [
                    'brand', 'size', 'color', 'fit', 'material', 'style', 'gender', 'condition',
                ],
                'chatbot_prompt_config' => [
                    'system_prompt_suffix' => 'You are an expert fashion assistant specializing in identifying clothing items. When analyzing images, pay close attention to brand labels, stitching patterns, fabric texture, wash type, and cut/fit. Ask about: brand, size (including specific measurements like waist/inseam for pants), color/wash, fit (slim, relaxed, bootcut, etc.), material, era/year if vintage, and any specific model names or style numbers.',
                    'key_attributes' => ['brand', 'size', 'color', 'fit', 'material', 'style', 'gender'],
                    'follow_up_topics' => ['condition preference', 'price range', 'size flexibility'],
                ],
                'is_active' => true,
            ]
        );

        $this->call(DemoSearchSeeder::class);
    }
}
