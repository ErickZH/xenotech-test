<?php

namespace App\Services\NotificationStrategies;

use App\Contracts\NotificationStrategyInterface;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VipUserNotificationStrategy implements NotificationStrategyInterface
{
    private string $webhookUrl = 'https://webhook.site/263d24fd-e9c9-485f-a981-9a6d0f5c95ec';

    public function sendNotification(Order $order): void
    {
        $payload = [
            'type' => 'whatsapp',
            'user_id' => $order->user_id,
            'user_email' => $order->user->email,
            'user_name' => $order->user->name,
            'user_type' => $order->user->type,
            'order_id' => $order->id,
            'order_status' => $order->status,
            'total_amount' => $order->total_amount,
            'timestamp' => now()->toISOString(),
        ];

        $response = Http::post($this->webhookUrl, $payload);

        Log::info('Notificación enviada a usuario vip', [
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'payload' => $payload,
            'response' => $response->body(),
        ]);
    }
}
