<?php

namespace App\Services;

use App\Contracts\NotificationStrategyInterface;
use App\Models\Order;
use App\Services\NotificationStrategies\PremiumUserNotificationStrategy;
use App\Services\NotificationStrategies\RegularUserNotificationStrategy;
use App\Services\NotificationStrategies\VipUserNotificationStrategy;

class NotificationService
{
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            'regular' => new RegularUserNotificationStrategy,
            'premium' => new PremiumUserNotificationStrategy,
            'vip' => new VipUserNotificationStrategy,
        ];
    }

    public function sendNotification(Order $order): void
    {
        $userType = $order->user->type ?? 'regular';
        $strategy = $this->getStrategy($userType);
        $strategy->sendNotification($order);
    }

    private function getStrategy(string $userType): NotificationStrategyInterface
    {
        return $this->strategies[$userType] ?? $this->strategies['regular'];
    }
}
