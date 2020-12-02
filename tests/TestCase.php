<?php

/*
 * This file is part of the Txtpay package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Tests;

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

    public function createEnvIfNotExist($path)
    {
        if (!file_exists($path)) {
            file_put_contents($path, '');
        }
    }

    public function loadEnv($env)
    {
        $this->createEnvIfNotExist($env);

        loadEnv($env);
    }
}
