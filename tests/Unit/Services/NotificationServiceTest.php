<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService;
    }

    public function test_uses_regular_strategy_for_regular_user(): void
    {
        // Arrange
        Log::shouldReceive('info')
            ->once()
            ->with('Notificación no enviada a usuario regular', [
                'user_id' => 1,
                'order_id' => 1,
            ]);

        $user = User::factory()->create([
            'id' => 1,
            'type' => 'regular',
        ]);

        $order = Order::factory()->create([
            'id' => 1,
            'user_id' => $user->id,
        ]);

        // Act
        $this->service->sendNotification($order);

        // Assert - Log assertion is handled by the mock above
        $this->assertTrue(true);
    }

    public function test_uses_premium_strategy_for_premium_user(): void
    {
        // Arrange
        Http::fake([
            'webhook.site/263d24fd-e9c9-485f-a981-9a6d0f5c95ec' => Http::response('{"success": true}', 200),
        ]);

        Log::shouldReceive('info')->once();

        $user = User::factory()->create([
            'type' => 'premium',
        ]);

        $order = Order::factory()->for($user)->create();

        // Act
        $this->service->sendNotification($order);

        // Assert
        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $payload['type'] === 'email';
        });
    }

    public function test_uses_vip_strategy_for_vip_user(): void
    {
        // Arrange
        Http::fake([
            'webhook.site/263d24fd-e9c9-485f-a981-9a6d0f5c95ec' => Http::response('{"success": true}', 200),
        ]);

        Log::shouldReceive('info')->once();

        $user = User::factory()->create([
            'type' => 'vip',
        ]);

        $order = Order::factory()->for($user)->create();

        // Act
        $this->service->sendNotification($order);

        // Assert
        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $payload['type'] === 'whatsapp';
        });
    }

    public function test_defaults_to_regular_strategy_for_null_user_type(): void
    {
        // Arrange
        Log::shouldReceive('info')->once();

        // Create user without specifying type, will use default 'regular'
        $user = User::factory()->create();

        $order = Order::factory()->for($user)->create();

        // Act
        $this->service->sendNotification($order);

        // Assert - Since user type defaults to 'regular', no HTTP request should be made
        Http::assertNothingSent();
    }

    public function test_defaults_to_regular_strategy_for_unknown_user_type(): void
    {
        // Arrange
        Log::shouldReceive('info')
            ->once()
            ->with('Notificación no enviada a usuario regular', [
                'user_id' => 1,
                'order_id' => 1,
            ]);

        $user = User::factory()->create([
            'id' => 1,
            'type' => 'unknown_type',
        ]);

        $order = Order::factory()->create([
            'id' => 1,
            'user_id' => $user->id,
        ]);

        // Act
        $this->service->sendNotification($order);

        // Assert - Log assertion is handled by the mock above
        $this->assertTrue(true);
    }
}
