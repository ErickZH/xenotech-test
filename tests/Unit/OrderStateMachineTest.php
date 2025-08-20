<?php

namespace Tests\Unit;

use App\Services\OrderStateMachine;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_transition_from_pending_to_processing()
    {
        $this->assertTrue(OrderStateMachine::canTransition('pending', 'processing'));
    }

    public function test_can_transition_from_pending_to_cancelled()
    {
        $this->assertTrue(OrderStateMachine::canTransition('pending', 'cancelled'));
    }

    public function test_can_transition_from_processing_to_shipped()
    {
        $this->assertTrue(OrderStateMachine::canTransition('processing', 'shipped'));
    }

    public function test_can_transition_from_processing_to_cancelled()
    {
        $this->assertTrue(OrderStateMachine::canTransition('processing', 'cancelled'));
    }

    public function test_can_transition_from_shipped_to_delivered()
    {
        $this->assertTrue(OrderStateMachine::canTransition('shipped', 'delivered'));
    }

    public function test_can_transition_from_shipped_to_cancelled()
    {
        $this->assertTrue(OrderStateMachine::canTransition('shipped', 'cancelled'));
    }

    public function test_can_transition_from_delivered_to_cancelled()
    {
        $this->assertTrue(OrderStateMachine::canTransition('delivered', 'cancelled'));
    }

    public function test_cannot_transition_from_cancelled_to_any_state()
    {
        $this->assertFalse(OrderStateMachine::canTransition('cancelled', 'pending'));
        $this->assertFalse(OrderStateMachine::canTransition('cancelled', 'processing'));
        $this->assertFalse(OrderStateMachine::canTransition('cancelled', 'shipped'));
        $this->assertFalse(OrderStateMachine::canTransition('cancelled', 'delivered'));
    }

    public function test_cannot_skip_states()
    {
        $this->assertFalse(OrderStateMachine::canTransition('pending', 'shipped'));
        $this->assertFalse(OrderStateMachine::canTransition('pending', 'delivered'));
        $this->assertFalse(OrderStateMachine::canTransition('processing', 'delivered'));
    }

    public function test_cannot_go_backwards()
    {
        $this->assertFalse(OrderStateMachine::canTransition('processing', 'pending'));
        $this->assertFalse(OrderStateMachine::canTransition('shipped', 'processing'));
        $this->assertFalse(OrderStateMachine::canTransition('delivered', 'shipped'));
    }

    public function test_get_available_transitions()
    {
        $this->assertEquals(['processing', 'cancelled'], OrderStateMachine::getAvailableTransitions('pending'));
        $this->assertEquals(['shipped', 'cancelled'], OrderStateMachine::getAvailableTransitions('processing'));
        $this->assertEquals(['delivered', 'cancelled'], OrderStateMachine::getAvailableTransitions('shipped'));
        $this->assertEquals(['cancelled'], OrderStateMachine::getAvailableTransitions('delivered'));
        $this->assertEquals([], OrderStateMachine::getAvailableTransitions('cancelled'));
    }

    public function test_all_valid_statuses()
    {
        $expected = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        $this->assertEquals($expected, OrderStateMachine::getAllStatuses());
    }

    public function test_is_valid_status()
    {
        $this->assertTrue(OrderStateMachine::isValidStatus('pending'));
        $this->assertTrue(OrderStateMachine::isValidStatus('processing'));
        $this->assertTrue(OrderStateMachine::isValidStatus('shipped'));
        $this->assertTrue(OrderStateMachine::isValidStatus('delivered'));
        $this->assertTrue(OrderStateMachine::isValidStatus('cancelled'));
        $this->assertFalse(OrderStateMachine::isValidStatus('invalid_status'));
    }
}
