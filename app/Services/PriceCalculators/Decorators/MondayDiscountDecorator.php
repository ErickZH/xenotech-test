<?php

namespace App\Services\PriceCalculators\Decorators;

use Carbon\Carbon;

class MondayDiscountDecorator extends PriceCalculatorDecorator
{
    private const DISCOUNT_PERCENTAGE = 10;

    public function calculate(float $baseAmount, array &$context = []): float
    {
        $amount = parent::calculate($baseAmount, $context);

        // Aplicar descuento solo los lunes
        if ($this->isMondayDiscount($context)) {
            $discount = $amount * (self::DISCOUNT_PERCENTAGE / 100);
            $amount = $amount - $discount;

            // Inicializar array si no existe
            if (! isset($context['applied_discounts'])) {
                $context['applied_discounts'] = [];
            }

            // Agregar información del descuento al contexto
            $context['applied_discounts'][] = [
                'type' => 'monday_discount',
                'percentage' => self::DISCOUNT_PERCENTAGE,
                'amount' => $discount,
                'description' => 'Descuento especial de lunes',
            ];
        }

        return round($amount, 2);
    }

    private function isMondayDiscount(array $context): bool
    {
        $date = $context['order_date'] ?? now();

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->isMonday();
    }
}
