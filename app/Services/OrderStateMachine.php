<?php

namespace App\Services;

use App\Models\Order;
use InvalidArgumentException;

class OrderStateMachine
{
    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSING = 'processing';

    const STATUS_SHIPPED = 'shipped';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Transiciones permitidas
     */
    private static array $allowedTransitions = [
        self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
        self::STATUS_PROCESSING => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
        self::STATUS_SHIPPED => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
        self::STATUS_DELIVERED => [self::STATUS_CANCELLED],
        self::STATUS_CANCELLED => [], // Estado terminal
    ];

    /**
     * Descripción de las transiciones
     */
    private static array $transitionDescriptions = [
        self::STATUS_PENDING => [
            self::STATUS_PROCESSING => 'Confirmar el pedido',
            self::STATUS_CANCELLED => 'Cancelar pedido pendiente',
        ],
        self::STATUS_PROCESSING => [
            self::STATUS_SHIPPED => 'Enviar el pedido',
            self::STATUS_CANCELLED => 'Cancelar pedido en proceso',
        ],
        self::STATUS_SHIPPED => [
            self::STATUS_DELIVERED => 'Confirmar entrega',
            self::STATUS_CANCELLED => 'Cancelar pedido enviado',
        ],
        self::STATUS_DELIVERED => [
            self::STATUS_CANCELLED => 'Cancelar pedido entregado',
        ],
    ];

    /**
     * Verifica si una transición es válida
     */
    public static function canTransition(string $fromStatus, string $toStatus): bool
    {
        if (! isset(self::$allowedTransitions[$fromStatus])) {
            return false;
        }

        return in_array($toStatus, self::$allowedTransitions[$fromStatus]);
    }

    /**
     * Obtiene los estados disponibles para transición desde un estado dado
     */
    public static function getAvailableTransitions(string $fromStatus): array
    {
        return self::$allowedTransitions[$fromStatus] ?? [];
    }

    /**
     * Obtiene la descripción de una transición
     */
    public static function getTransitionDescription(string $fromStatus, string $toStatus): string
    {
        return self::$transitionDescriptions[$fromStatus][$toStatus] ?? 'Transición no definida';
    }

    /**
     * Valida y ejecuta una transición de estado
     */
    public static function transition(Order $order, string $newStatus): Order
    {
        $currentStatus = $order->status;

        if (! self::canTransition($currentStatus, $newStatus)) {
            $availableStates = implode(', ', self::getAvailableTransitions($currentStatus));
            throw new InvalidArgumentException(
                "No se puede cambiar el estado de '{$currentStatus}' a '{$newStatus}'. ".
                'Estados disponibles: '.($availableStates ?: 'ninguno')
            );
        }

        $order->status = $newStatus;
        $order->save();

        // Send notification when status changes
        $notificationService = app(NotificationService::class);
        $notificationService->sendNotification($order);

        return $order;
    }

    /**
     * Obtiene todos los estados válidos
     */
    public static function getAllStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Verifica si un estado es válido
     */
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::getAllStatuses());
    }
}
