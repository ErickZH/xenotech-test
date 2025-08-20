<?php

namespace Tests\Unit\Services\NotificationStrategies;

use App\Models\Order;
use App\Models\User;
use App\Services\NotificationStrategies\PremiumUserNotificationStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PremiumUserNotificationStrategyTest extends TestCase
{
    use RefreshDatabase;

    private PremiumUserNotificationStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new PremiumUserNotificationStrategy;
    }

    public function test_sends_email_notification_and_logs_with_payload(): void
    {
        // Arrange
        $responseBody = '{"success": true}';

        Http::fake([
            'webhook.site/263d24fd-e9c9-485f-a981-9a6d0f5c95ec' => Http::response($responseBody, 200),
        ]);

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'premium',
        ]);

        $order = Order::factory()->for($user)->create([
            'status' => 'processing',
            'total_amount' => 100.50,
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Notificación enviada a usuario premium', Mockery::on(function ($arg) use ($user, $order, $responseBody) {
                return $arg['user_id'] === $user->id &&
                       $arg['order_id'] === $order->id &&
                       isset($arg['payload']) &&
                       is_array($arg['payload']) &&
                       $arg['payload']['type'] === 'email' &&
                       $arg['payload']['user_id'] === $user->id &&
                       $arg['payload']['user_email'] === $user->email &&
                       $arg['payload']['user_name'] === $user->name &&
                       $arg['payload']['user_type'] === $user->type &&
                       $arg['payload']['order_id'] === $order->id &&
                       $arg['payload']['order_status'] === $order->status &&
                       $arg['payload']['total_amount'] === $order->total_amount &&
                       isset($arg['payload']['timestamp']) &&
                       $arg['response'] === $responseBody;
            }));

        // Act
        $this->strategy->sendNotification($order);

        // Assert
        Http::assertSent(function ($request) use ($order, $user) {
            $payload = $request->data();

            return $request->url() === 'https://webhook.site/263d24fd-e9c9-485f-a981-9a6d0f5c95ec' &&
                   $payload['type'] === 'email' &&
                   $payload['user_id'] === $user->id &&
                   $payload['user_email'] === $user->email &&
                   $payload['user_name'] === $user->name &&
                   $payload['user_type'] === $user->type &&
                   $payload['order_id'] === $order->id &&
                   $payload['order_status'] === $order->status &&
                   $payload['total_amount'] === $order->total_amount &&
                   isset($payload['timestamp']);
        });
    }
}
