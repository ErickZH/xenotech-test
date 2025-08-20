<?php

namespace App\Contracts;

interface PriceCalculatorInterface
{
    public function calculate(float $baseAmount, array &$context = []): float;
}
