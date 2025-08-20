<?php

namespace App\Services\PriceCalculators;

use App\Contracts\PriceCalculatorInterface;

class BasePriceCalculator implements PriceCalculatorInterface
{
    public function calculate(float $baseAmount, array &$context = []): float
    {
        return $baseAmount;
    }
}
