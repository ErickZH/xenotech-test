<?php

namespace App\Services\PriceCalculators\Decorators;

use Carbon\Carbon;

class RandomDiscountDecorator extends PriceCalculatorDecorator
{
    private const MIN_DISCOUNT = 1;

    private const MAX_DISCOUNT = 3;

    private const VALID_DAYS = [1, 2, 3, 4]; // Lunes a Jueves

    public function calculate(float $baseAmount, array &$context = []): float
    {
        $amount = parent::calculate($baseAmount, $context);

        // Aplicar descuento aleatorio de lunes a jueves
        if ($this->isValidDayForRandomDiscount($context)) {
            $discountPercentage = $this->getRandomDiscountPercentage();
            $discount = $amount * ($discountPercentage / 100);
            $amount = $amount - $discount;

            // Inicializar array si no existe
            if (! isset($context['applied_discounts'])) {
                $context['applied_discounts'] = [];
            }

            // Agregar información del descuento al contexto
            $context['applied_discounts'][] = [
                'type' => 'random_discount',
                'percentage' => $discountPercentage,
                'amount' => $discount,
                'description' => "Descuento aleatorio del {$discountPercentage}%",
            ];
        }

        return round($amount, 2);
    }

    private function isValidDayForRandomDiscount(array $context): bool
    {
        $date = $context['order_date'] ?? now();

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return in_array($date->dayOfWeek, self::VALID_DAYS);
    }

    private function getRandomDiscountPercentage(): float
    {
        // Generar descuento aleatorio entre 1% y 3%
        return round(
            self::MIN_DISCOUNT + (mt_rand() / mt_getrandmax()) * (self::MAX_DISCOUNT - self::MIN_DISCOUNT),
            2
        );
    }
}
