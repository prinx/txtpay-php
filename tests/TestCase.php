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

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base Test case.
 */
abstract class TestCase extends BaseTestCase
{
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
