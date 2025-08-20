<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UpdateOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_order_requires_status()
    {
        $order = Order::factory()->create();

        $this->putJson("/api/orders/{$order->id}")->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_update_order_validates_status_value()
    {
        $order = Order::factory()->create();

        $this->putJson("/api/orders/{$order->id}", [
            'status' => 'invalid-status',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_update_order_requires_valid_order()
    {
        $this->putJson('/api/orders/999')->assertStatus(404);
    }

    public function test_update_order_from_pending_to_processing()
    {
        $order = Order::factory()->pending()->create();

        $this->putJson("/api/orders/{$order->id}", [
            'status' => 'processing',
        ])->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'processing',
                ],
            ]);
    }

    public function test_update_order_from_processing_to_shipped()
    {
        $order = Order::factory()->processing()->create();

        $this->putJson("/api/orders/{$order->id}", [
            'status' => 'shipped',
        ])->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'shipped',
                ],
            ]);
    }

    public function test_update_order_from_shipped_to_delivered()
    {
        $order = Order::factory()->shipped()->create();

        $this->putJson("/api/orders/{$order->id}", [
            'status' => 'delivered',
        ])->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'delivered',
                ],
            ]);
    }

    public function test_update_order_from_any_status_to_cancelled()
    {
        $order = Order::factory()->processing()->create();

        $this->putJson("/api/orders/{$order->id}", [
            'status' => 'cancelled',
        ])->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);
    }

    public function test_update_order_invalid_transition_returns_422()
    {
        $order = Order::factory()->pending()->create();

        $this->putJson("/api/orders/{$order->id}", [
            'status' => 'invalid-status',
        ])->assertStatus(422);
    }

    public function test_update_order_invalid_transition_shows_available_states()
    {
        $order = Order::factory()->pending()->create();

        $this->putJson("/api/orders/{$order->id}", [
            'status' => 'delivered',
        ])->assertStatus(422)
            ->assertJson([
                'available_transitions' => ['processing', 'cancelled'],
            ]);
    }

    public function test_update_order_invalid_transition_preserves_current_state()
    {
        $order = Order::factory()->pending()->create();

        $this->putJson("/api/orders/{$order->id}", [
            'status' => 'delivered',
        ])->assertStatus(422);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_update_order_triggers_notification_on_status_change()
    {
        $user = User::factory()->create(['type' => 'premium']);
        $order = Order::factory()->for($user)->pending()->create();

        Log::shouldReceive('info')->once()->withArgs(function ($message, $context) use ($user, $order) {
            return $message === 'Notificación enviada a usuario premium'
                && $context['user_id'] === $user->id
                && $context['order_id'] === $order->id;
        });

        $this->putJson("/api/orders/{$order->id}", [
            'status' => 'processing',
        ])->assertStatus(200);
    }
}
