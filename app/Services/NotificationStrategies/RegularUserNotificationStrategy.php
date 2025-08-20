<?php

namespace App\Services\NotificationStrategies;

use App\Contracts\NotificationStrategyInterface;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class RegularUserNotificationStrategy implements NotificationStrategyInterface
{
    public function sendNotification(Order $order): void
    {
        Log::info('Notificación no enviada a usuario regular', [
            'user_id' => $order->user_id,
            'order_id' => $order->id,
        ]);
    }
}
