<?php

namespace Tests\Unit\Services\NotificationStrategies;

use App\Models\Order;
use App\Models\User;
use App\Services\NotificationStrategies\RegularUserNotificationStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RegularUserNotificationStrategyTest extends TestCase
{
    use RefreshDatabase;

    private RegularUserNotificationStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new RegularUserNotificationStrategy;
    }

    public function test_sends_no_notification_but_logs_for_regular_user(): void
    {
        // Arrange
        Log::shouldReceive('info')
            ->once()
            ->with('Notificación no enviada a usuario regular', [
                'user_id' => 1,
                'order_id' => 1,
            ]);

        $user = User::factory()->create([
            'type' => 'regular',
        ]);

        $order = Order::factory()->for($user)->create([
            'status' => 'pending',
        ]);

        // Act
        $this->strategy->sendNotification($order);

        // Assert - Log assertion is handled by the mock above
        $this->assertTrue(true);
    }
}
