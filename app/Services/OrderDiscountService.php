<?php

namespace App\Services;

use App\Contracts\PriceCalculatorInterface;
use App\Services\PriceCalculators\BasePriceCalculator;
use App\Services\PriceCalculators\Decorators\MondayDiscountDecorator;
use App\Services\PriceCalculators\Decorators\RandomDiscountDecorator;

class OrderDiscountService
{
    /**
     * Calcula el precio final de la orden aplicando todos los descuentos disponibles
     */
    public function calculateFinalAmount(float $baseAmount, array $context = []): array
    {
        // Inicializar el contexto para almacenar descuentos aplicados
        $context['applied_discounts'] = [];

        // Construir la cadena de decoradores
        $calculator = $this->buildCalculatorChain();

        // Calcular el precio final
        $finalAmount = $calculator->calculate($baseAmount, $context);

        // Calcular el total de descuentos
        $totalDiscounts = array_sum(array_column($context['applied_discounts'], 'amount'));

        return [
            'original_amount' => $baseAmount,
            'final_amount' => $finalAmount,
            'total_discounts' => round($totalDiscounts, 2),
            'discounts_applied' => $context['applied_discounts'],
            'savings_percentage' => $baseAmount > 0 ? round(($totalDiscounts / $baseAmount) * 100, 2) : 0,
        ];
    }

    /**
     * Construye la cadena de decoradores
     */
    private function buildCalculatorChain(): PriceCalculatorInterface
    {
        // Empezar con el calculador base
        $calculator = new BasePriceCalculator;

        // Aplicar decorador de descuento de lunes
        $calculator = new MondayDiscountDecorator($calculator);

        // Aplicar decorador de descuento aleatorio
        $calculator = new RandomDiscountDecorator($calculator);

        return $calculator;
    }

    /**
     * Verifica si hay descuentos disponibles para una fecha específica
     */
    public function hasAvailableDiscounts(?\DateTime $date = null): bool
    {
        $date = $date ?? now();
        $dayOfWeek = $date->format('N'); // 1 (lunes) a 7 (domingo)

        // Hay descuentos disponibles de lunes (1) a jueves (4)
        return $dayOfWeek >= 1 && $dayOfWeek <= 4;
    }
}
