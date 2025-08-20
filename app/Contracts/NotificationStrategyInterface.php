<?php

namespace App\Contracts;

use App\Models\Order;

interface NotificationStrategyInterface
{
    public function sendNotification(Order $order): void;
}
