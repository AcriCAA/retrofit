<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\SearchRequest;
use App\Models\SearchRequestAttribute;
use App\Models\SearchResult;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSearchSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $category = ProductCategory::where('slug', 'clothing')->first();

        if (! $user || ! $category) {
            $this->command->error('Run DatabaseSeeder first to create the test user and clothing category.');
            return;
        }

        // Search 1: Active search with results — simulates a search that has run 3 times
        $search1 = SearchRequest::create([
            'user_id' => $user->id,
            'product_category_id' => $category->id,
            'title' => "Levi's 501 Original Fit — Dark Wash, 32x30",
            'description' => "Looking for Levi's 501 Original Fit jeans in a dark indigo wash. Size 32 waist, 30 length. Preferably pre-owned in good condition.",
            'status' => 'active',
            'search_frequency_minutes' => 60,
            'min_price' => 15.00,
            'max_price' => 75.00,
            'last_searched_at' => now()->subMinutes(23),
        ]);

        foreach ([
            ['key' => 'brand', 'value' => "Levi's"],
            ['key' => 'style', 'value' => '501 Original Fit'],
            ['key' => 'color', 'value' => 'Dark indigo wash'],
            ['key' => 'size', 'value' => '32W x 30L'],
            ['key' => 'gender', 'value' => 'Men'],
            ['key' => 'condition', 'value' => 'Good or better'],
        ] as $attr) {
            SearchRequestAttribute::create([
                'search_request_id' => $search1->id,
                ...$attr,
            ]);
        }

        $results1 = [
            [
                'marketplace' => 'ebay',
                'external_id' => '395182746301',
                'title' => "Levi's 501 Original Fit Jeans Men's 32x30 Dark Wash Button Fly Denim",
                'description' => 'Classic 501 in excellent condition. Dark rinse wash, minimal fading. Button fly. Measurements verified.',
                'price' => 34.99,
                'condition' => 'Pre-owned',
                'seller_name' => 'denim_vault_99',
                'url' => 'https://www.ebay.com/itm/395182746301',
                'image_url' => 'https://picsum.photos/seed/levis501a/400/400',
                'relevance_score' => 0.9512,
                'user_status' => 'new',
                'is_notified' => true,
                'created_at' => now()->subMinutes(23),
            ],
            [
                'marketplace' => 'poshmark',
                'external_id' => 'pm-65a2b1c3d4e5',
                'title' => "Men's Levi's 501 Jeans 32/30 Dark Blue Wash Straight Leg",
                'description' => 'Worn a handful of times. Great dark wash that has not faded. True to size.',
                'price' => 28.00,
                'condition' => 'Good',
                'seller_name' => 'closet_refresh',
                'url' => 'https://poshmark.com/listing/65a2b1c3d4e5',
                'image_url' => 'https://picsum.photos/seed/levis501b/400/400',
                'relevance_score' => 0.9234,
                'user_status' => 'saved',
                'is_notified' => true,
                'created_at' => now()->subHours(2),
            ],
            [
                'marketplace' => 'mercari',
                'external_id' => 'm-98231847562',
                'title' => "Levi's 501 Original Jeans Dark Indigo 32x30 Like New",
                'description' => 'Basically brand new, only tried on. Perfect dark indigo color.',
                'price' => 45.00,
                'condition' => 'Like new',
                'seller_name' => 'thrift_finds_daily',
                'url' => 'https://www.mercari.com/us/item/98231847562',
                'image_url' => 'https://picsum.photos/seed/levis501c/400/400',
                'relevance_score' => 0.9678,
                'user_status' => 'new',
                'is_notified' => true,
                'created_at' => now()->subMinutes(23),
            ],
            [
                'marketplace' => 'grailed',
                'external_id' => 'gr-44019283',
                'title' => "Vintage Levi's 501 Selvedge Dark Wash 32x30",
                'description' => 'Vintage 501s with selvedge denim. Dark wash, some natural distressing on the hem.',
                'price' => 72.00,
                'condition' => 'Good',
                'seller_name' => 'raw_denim_co',
                'url' => 'https://www.grailed.com/listings/44019283',
                'image_url' => 'https://picsum.photos/seed/levis501d/400/400',
                'relevance_score' => 0.8845,
                'user_status' => 'viewed',
                'is_notified' => true,
                'created_at' => now()->subHours(5),
            ],
            [
                'marketplace' => 'ebay',
                'external_id' => '395193827456',
                'title' => "Levi's 501 Button Fly Jeans 32x30 Dark Rinse Denim Straight Fit Men",
                'description' => 'Good used condition. Some slight fading at the knees but overall dark wash is intact.',
                'price' => 22.50,
                'condition' => 'Pre-owned',
                'seller_name' => 'bargain_closet',
                'url' => 'https://www.ebay.com/itm/395193827456',
                'image_url' => 'https://picsum.photos/seed/levis501e/400/400',
                'relevance_score' => 0.8123,
                'user_status' => 'dismissed',
                'is_notified' => true,
                'created_at' => now()->subHours(5),
            ],
            [
                'marketplace' => 'thredup',
                'external_id' => 'td-77291034',
                'title' => "Levi's 501 Straight Leg Jeans Size 32 Dark Wash",
                'description' => null,
                'price' => 18.99,
                'condition' => 'Good',
                'seller_name' => 'thredUP',
                'url' => 'https://www.thredup.com/product/77291034',
                'image_url' => 'https://picsum.photos/seed/levis501f/400/400',
                'relevance_score' => 0.7956,
                'user_status' => 'new',
                'is_notified' => false,
                'created_at' => now()->subMinutes(23),
            ],
        ];

        foreach ($results1 as $result) {
            SearchResult::create([
                'search_request_id' => $search1->id,
                'currency' => 'USD',
                ...$result,
            ]);
        }

        // Search 2: Paused search, no recent results
        $search2 = SearchRequest::create([
            'user_id' => $user->id,
            'product_category_id' => $category->id,
            'title' => 'Vintage Nike Windbreaker — Black/Red, Large',
            'description' => 'Looking for a 90s Nike windbreaker in black with red accents. Size Large.',
            'status' => 'paused',
            'search_frequency_minutes' => 360,
            'min_price' => null,
            'max_price' => 120.00,
            'last_searched_at' => now()->subDays(3),
        ]);

        foreach ([
            ['key' => 'brand', 'value' => 'Nike'],
            ['key' => 'style', 'value' => 'Windbreaker'],
            ['key' => 'color', 'value' => 'Black / Red'],
            ['key' => 'size', 'value' => 'Large'],
            ['key' => 'material', 'value' => 'Nylon'],
        ] as $attr) {
            SearchRequestAttribute::create([
                'search_request_id' => $search2->id,
                ...$attr,
            ]);
        }

        // Search 3: Brand new search, never searched yet
        $search3 = SearchRequest::create([
            'user_id' => $user->id,
            'product_category_id' => $category->id,
            'title' => 'Carhartt WIP Detroit Jacket — Hamilton Brown, Medium',
            'description' => null,
            'status' => 'active',
            'search_frequency_minutes' => 120,
            'min_price' => 50.00,
            'max_price' => 200.00,
            'last_searched_at' => null,
        ]);

        foreach ([
            ['key' => 'brand', 'value' => 'Carhartt WIP'],
            ['key' => 'style', 'value' => 'Detroit Jacket'],
            ['key' => 'color', 'value' => 'Hamilton Brown'],
            ['key' => 'size', 'value' => 'Medium'],
        ] as $attr) {
            SearchRequestAttribute::create([
                'search_request_id' => $search3->id,
                ...$attr,
            ]);
        }

        $this->command->info('Demo searches created: 3 searches, 6 results.');
    }
}
