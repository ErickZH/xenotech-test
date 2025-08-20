<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_requires_user_id(): void
    {
        $response = $this->postJson('/api/orders', [
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 1,
                    'price' => 10.00,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_create_order_requires_items_array(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_create_order_requires_valid_user_id(): void
    {
        $response = $this->postJson('/api/orders', [
            'user_id' => 999999,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 1,
                    'price' => 10.00,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_create_order_items_require_price(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.price']);
    }

    public function test_create_order_items_require_quantity(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'price' => 10.00,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_create_order_items_require_product_name(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'quantity' => 1,
                    'price' => 10.00,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_name']);
    }

    public function test_create_order_rejects_empty_items_array(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_create_order_validates_item_price_is_numeric(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 1,
                    'price' => 'not-a-number',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.price']);
    }

    public function test_create_order_validates_item_quantity_is_integer(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 'not-an-integer',
                    'price' => 10.00,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_create_order_validates_item_quantity_minimum(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 0,
                    'price' => 10.00,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_create_order_validates_item_price_minimum(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 1,
                    'price' => -1,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.price']);
    }

    public function test_create_order_validates_user_id_is_integer(): void
    {
        $response = $this->postJson('/api/orders', [
            'user_id' => 'not-an-integer',
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 1,
                    'price' => 10.00,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_create_order_with_single_item(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 2,
                    'price' => 25.50,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'original_amount',
                    'discount_amount',
                    'total_amount',
                    'status',
                    'items',
                    'user',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Test Product',
            'quantity' => 2,
            'price' => 25.50,
        ]);
    }

    public function test_create_order_with_multiple_items(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Product A',
                    'quantity' => 1,
                    'price' => 10.00,
                ],
                [
                    'product_name' => 'Product B',
                    'quantity' => 3,
                    'price' => 15.50,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Product A',
            'quantity' => 1,
            'price' => 10.00,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Product B',
            'quantity' => 3,
            'price' => 15.50,
        ]);
    }

    public function test_create_order_calculates_base_amount_correctly(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Product A',
                    'quantity' => 2,
                    'price' => 10.00,
                ],
                [
                    'product_name' => 'Product B',
                    'quantity' => 1,
                    'price' => 25.50,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'original_amount' => 45.50,
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'original_amount' => 45.50,
        ]);
    }

    public function test_create_order_stores_all_order_data(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 1,
                    'price' => 20.00,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'original_amount' => 20.00,
            'status' => 'pending',
        ]);

        $response->assertJsonStructure([
            'data' => [
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
        ]);
    }

    public function test_create_order_creates_order_items(): void
    {
        $user = User::factory()->create();

        $items = [
            [
                'product_name' => 'Laptop',
                'quantity' => 1,
                'price' => 999.99,
            ],
            [
                'product_name' => 'Mouse',
                'quantity' => 2,
                'price' => 25.00,
            ],
        ];

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => $items,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);

        foreach ($items as $item) {
            $this->assertDatabaseHas('order_items', [
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        $response->assertJsonCount(2, 'data.items');
    }

    public function test_create_order_applies_discounts_correctly(): void
    {
        $user = User::factory()->create();

        $discountService = $this->createMock(OrderDiscountService::class);
        $discountService->method('calculateFinalAmount')
            ->willReturn([
                'original_amount' => 100.00,
                'final_amount' => 85.00,
                'total_discounts' => 15.00,
                'discounts_applied' => [
                    [
                        'type' => 'monday_discount',
                        'amount' => 10.00,
                        'description' => 'Monday discount 10%',
                    ],
                    [
                        'type' => 'random_discount',
                        'amount' => 5.00,
                        'description' => 'Random discount 5%',
                    ],
                ],
            ]);

        $this->app->instance(OrderDiscountService::class, $discountService);

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 1,
                    'price' => 100.00,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'original_amount' => 100.00,
                    'discount_amount' => 15.00,
                    'total_amount' => 85.00,
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'original_amount' => 100.00,
            'discount_amount' => 15.00,
            'total_amount' => 85.00,
        ]);
    }

    public function test_create_order_stores_discount_details(): void
    {
        $user = User::factory()->create();

        $discountDetails = [
            [
                'type' => 'monday_discount',
                'amount' => 20.00,
                'description' => 'Monday discount 20%',
            ],
        ];

        $discountService = $this->createMock(OrderDiscountService::class);
        $discountService->method('calculateFinalAmount')
            ->willReturn([
                'original_amount' => 100.00,
                'final_amount' => 80.00,
                'total_discounts' => 20.00,
                'discounts_applied' => $discountDetails,
            ]);

        $this->app->instance(OrderDiscountService::class, $discountService);

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 1,
                    'price' => 100.00,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'discount_details' => $discountDetails,
                ],
            ]);

        $order = Order::first();
        $this->assertEquals($discountDetails, $order->discount_details);
    }

    public function test_create_order_calculates_final_amount_with_discounts(): void
    {
        $user = User::factory()->create();

        $discountService = $this->createMock(OrderDiscountService::class);
        $discountService->method('calculateFinalAmount')
            ->with(150.00, $this->callback(function ($context) use ($user) {
                return isset($context['order_date']) &&
                    $context['user_id'] === $user->id;
            }))
            ->willReturn([
                'original_amount' => 150.00,
                'final_amount' => 120.00,
                'total_discounts' => 30.00,
                'discounts_applied' => [
                    [
                        'type' => 'bulk_discount',
                        'amount' => 30.00,
                        'description' => 'Bulk purchase discount',
                    ],
                ],
            ]);

        $this->app->instance(OrderDiscountService::class, $discountService);

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Product A',
                    'quantity' => 3,
                    'price' => 50.00,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'original_amount' => 150.00,
                    'discount_amount' => 30.00,
                    'total_amount' => 120.00,
                ],
            ]);
    }

    public function test_create_order_handles_zero_discounts(): void
    {
        $user = User::factory()->create();

        $discountService = $this->createMock(OrderDiscountService::class);
        $discountService->method('calculateFinalAmount')
            ->willReturn([
                'original_amount' => 50.00,
                'final_amount' => 50.00,
                'total_discounts' => 0.00,
                'discounts_applied' => [],
            ]);

        $this->app->instance(OrderDiscountService::class, $discountService);

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 1,
                    'price' => 50.00,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'original_amount' => 50.00,
                    'discount_amount' => 0.00,
                    'total_amount' => 50.00,
                    'discount_details' => [],
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'original_amount' => 50.00,
            'discount_amount' => 0.00,
            'total_amount' => 50.00,
        ]);
    }

    public function test_create_order_sets_default_status()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 1,
                    'price' => 50.00,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }
}
