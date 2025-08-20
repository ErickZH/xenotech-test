<?php

namespace Tests\Unit\Services;

use App\Services\OrderDiscountService;
use App\Services\PriceCalculators\BasePriceCalculator;
use App\Services\PriceCalculators\Decorators\MondayDiscountDecorator;
use App\Services\PriceCalculators\Decorators\RandomDiscountDecorator;
use Carbon\Carbon;
use Tests\TestCase;

class OrderDiscountTest extends TestCase
{
    public function test_base_calculator_returns_same_amount()
    {
        $calculator = new BasePriceCalculator;
        $result = $calculator->calculate(100.00);

        $this->assertEquals(100.00, $result);
    }

    public function test_monday_discount_applies_on_monday()
    {
        $baseCalculator = new BasePriceCalculator;
        $mondayDecorator = new MondayDiscountDecorator($baseCalculator);

        $mondayContext = ['order_date' => Carbon::createFromDate(2024, 1, 1)->next(Carbon::MONDAY)];

        $result = $mondayDecorator->calculate(100.00, $mondayContext);

        // Debería aplicar 10% de descuento = 90.00
        $this->assertEquals(90.00, $result);
    }

    public function test_monday_discount_does_not_apply_on_other_days()
    {
        $baseCalculator = new BasePriceCalculator;
        $mondayDecorator = new MondayDiscountDecorator($baseCalculator);

        $tuesdayContext = ['order_date' => Carbon::createFromDate(2024, 1, 1)->next(Carbon::TUESDAY)];

        $result = $mondayDecorator->calculate(100.00, $tuesdayContext);

        // No debería aplicar descuento
        $this->assertEquals(100.00, $result);
    }

    public function test_random_discount_applies_monday_to_thursday()
    {
        $baseCalculator = new BasePriceCalculator;
        $randomDecorator = new RandomDiscountDecorator($baseCalculator);

        // Testear lunes a jueves
        $days = [Carbon::MONDAY, Carbon::TUESDAY, Carbon::WEDNESDAY, Carbon::THURSDAY];

        foreach ($days as $day) {
            $context = ['order_date' => Carbon::createFromDate(2024, 1, 1)->next($day)];
            $result = $randomDecorator->calculate(100.00, $context);

            // Debería aplicar algún descuento (entre 1% y 3%)
            $this->assertLessThan(100.00, $result);
            $this->assertGreaterThanOrEqual(97.00, $result); // Máximo 3% descuento
        }
    }

    public function test_random_discount_does_not_apply_friday_to_sunday()
    {
        $baseCalculator = new BasePriceCalculator;
        $randomDecorator = new RandomDiscountDecorator($baseCalculator);

        // Testear viernes a domingo
        $days = [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY];

        foreach ($days as $day) {
            $context = ['order_date' => Carbon::createFromDate(2024, 1, 1)->next($day)];
            $result = $randomDecorator->calculate(100.00, $context);

            // No debería aplicar descuento
            $this->assertEquals(100.00, $result);
        }
    }

    public function test_combined_decorators_on_monday()
    {
        $baseCalculator = new BasePriceCalculator;
        $mondayDecorator = new MondayDiscountDecorator($baseCalculator);
        $randomDecorator = new RandomDiscountDecorator($mondayDecorator);

        $mondayContext = ['order_date' => Carbon::createFromDate(2024, 1, 1)->next(Carbon::MONDAY)];
        $result = $randomDecorator->calculate(100.00, $mondayContext);

        // Debería aplicar ambos descuentos
        // Mínimo: 10% (lunes) + 1% (aleatorio) = al menos 11% descuento
        $this->assertLessThan(90.00, $result);
    }

    public function test_discount_service_calculates_correctly()
    {
        $discountService = new OrderDiscountService;

        // Simular un lunes
        $mondayContext = ['order_date' => Carbon::createFromDate(2024, 1, 1)->next(Carbon::MONDAY)];
        $result = $discountService->calculateFinalAmount(100.00, $mondayContext);

        $this->assertArrayHasKey('original_amount', $result);
        $this->assertArrayHasKey('final_amount', $result);
        $this->assertArrayHasKey('total_discounts', $result);
        $this->assertArrayHasKey('discounts_applied', $result);
        $this->assertArrayHasKey('savings_percentage', $result);

        $this->assertEquals(100.00, $result['original_amount']);
        $this->assertLessThan(100.00, $result['final_amount']);
        $this->assertGreaterThan(0, $result['total_discounts']);
    }

    public function test_has_available_discounts()
    {
        $discountService = new OrderDiscountService;

        // Lunes a jueves deberían tener descuentos disponibles
        $mondayDate = Carbon::createFromDate(2024, 1, 1)->next(Carbon::MONDAY);
        $this->assertTrue($discountService->hasAvailableDiscounts($mondayDate));

        // Viernes a domingo no deberían tener descuentos
        $fridayDate = Carbon::createFromDate(2024, 1, 1)->next(Carbon::FRIDAY);
        $this->assertFalse($discountService->hasAvailableDiscounts($fridayDate));
    }
}
