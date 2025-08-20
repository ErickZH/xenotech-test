<?php

namespace App\Services\PriceCalculators\Decorators;

use App\Contracts\PriceCalculatorInterface;

abstract class PriceCalculatorDecorator implements PriceCalculatorInterface
{
    protected PriceCalculatorInterface $priceCalculator;

    public function __construct(PriceCalculatorInterface $priceCalculator)
    {
        $this->priceCalculator = $priceCalculator;
    }

    public function calculate(float $baseAmount, array &$context = []): float
    {
        return $this->priceCalculator->calculate($baseAmount, $context);
    }
}
