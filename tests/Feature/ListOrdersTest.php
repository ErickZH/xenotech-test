<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ListOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_are_paginated_with_default_per_page()
    {
        Order::factory()->count(30)->create();

        $response = $this->getJson('/api/orders');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json->has('meta')
                ->has('data', 10)
                ->has('links')
        );
    }

    public function test_orders_respect_custom_per_page_parameter()
    {
        Order::factory()->count(30)->create();

        $response = $this->getJson('/api/orders?per_page=15');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json->has('meta')
                ->has('data', 15)
                ->has('links')
        );
    }

    public function test_orders_limit_max_per_page_to_30()
    {
        Order::factory()->count(30)->create();

        $response = $this->getJson('/api/orders?per_page=50');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json->has('meta')
                ->has('data', 30)
                ->has('links')
        );
    }

    public function test_pagination_includes_meta_data_and_links()
    {
        Order::factory()->count(30)->create();

        $response = $this->getJson('/api/orders');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json->has('meta')
                ->has('data', 10)
                ->has('links')
        );
    }

    public function test_filter_orders_by_status()
    {
        Order::factory()->count(30)->create();

        $response = $this->getJson('/api/orders?status=pending');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json->has('meta')
                ->has('data')
                ->has(
                    'data.0',
                    fn (AssertableJson $json) => $json->where('status', 'pending')
                        ->etc()
                )
                ->has('links')
        );
    }

    public function test_filter_orders_by_user_id()
    {
        Order::factory()->count(30)->create();

        $response = $this->getJson('/api/orders?user_id=1');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json->has('meta')
                ->has('data', 1)
                ->has(
                    'data.0',
                    fn ($json) => $json->where('user_id', 1)
                        ->etc()
                )
                ->has('links')
        );
    }

    public function test_filter_orders_by_multiple_parameters()
    {
        Order::factory()->pending()->count(10)->create();

        $response = $this->getJson('/api/orders?status=pending&user_id=1');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json->has('meta')
                ->has('data', 1)
                ->has(
                    'data.0',
                    fn ($json) => $json->where('status', 'pending')
                        ->where('user_id', 1)
                        ->etc()
                )
                ->has('links')
        );
    }

    public function test_filter_returns_empty_when_no_matches()
    {
        Order::factory()->count(30)->create();

        $response = $this->getJson('/api/orders?status=pending&user_id=999');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json->has('meta')
                ->has('data', 0)
                ->has('links')
        );
    }

    public function test_orders_include_items_and_user_relationships()
    {
        $order = Order::factory()->hasItems(1)->create();

        $response = $this->getJson('/api/orders');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json->has('data')
                ->has('data.0.items', 1)
                ->has(
                    'data.0.user',
                    fn (AssertableJson $json) => $json->where('id', $order->user_id)
                        ->etc()
                )->etc()
        );
    }

    public function test_order_resource_structure()
    {
        Order::factory()->hasItems(1)->count(10)->create();

        $response = $this->getJson('/api/orders');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'original_amount',
                    'discount_amount',
                    'total_amount',
                    'status',
                    'discount_details',
                    'items_count',
                    'items' => [
                        '*' => [
                            'id',
                            'product_name',
                            'quantity',
                            'price',
                        ],
                    ],
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'created_at',
                ],
            ],
            'links',
            'meta',
        ]);
    }
}
