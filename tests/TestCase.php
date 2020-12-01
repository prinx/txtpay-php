<?php

namespace Tests;

use Closure;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase as BaseTestCase;
use stdClass;

/**
 * Base Test case.
 */
class TestCase extends BaseTestCase
{
    /**
     * Just to shutdown the warning.
     * WARN  PHPUnit\Framework\WarningTestCase
     * ! warning → No tests found in class "Tests\TestCase".
     */
    public function testTxtpay()
    {
        $this->assertTrue(1 === 1);
    }

    public function shouldBeCalled(InvocationOrder $times = null)
    {
        $method = '__invoke';
        $shouldBeCalled = $this->getMockBuilder(stdClass::class)
            ->addMethods([$method])
            ->getMock();
        $shouldBeCalled->expects($times ?: $this->once())
            ->method($method);

        return $shouldBeCalled;
    }
}
