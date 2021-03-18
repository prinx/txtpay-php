<?php

/*
 * This file is part of the Txtpay package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Tests\Unit;

use Tests\TestCase;
use Txtpay\Callback;
use Txtpay\Exceptions\InvalidCallbackHandlerException;
use Txtpay\Helpers\Tests\CallbackHandlerMustThrowException;
use Txtpay\MobileMoney;

class CallbackHandlerMustThrowExceptionTest extends TestCase
{
    public function testCallbackHandlerMustThrowInvalidCallbackHandlerException()
    {
        $this->loadEnv(realpath(__DIR__.'/../../').'/.env');

        $this->expectException(InvalidCallbackHandlerException::class);

        $_POST = [
            'code'              => '000',
            'status'            => 'test',
            'reason'            => 'test',
            'transaction_id'    => (new MobileMoney)->getTransactionId(),
            'r_switch'          => 'test',
            'subscriber_number' => '23354545454545',
            'amount'            => 1,
            'currency'          => 'GHS',
        ];

        $callback = new Callback;

        $callback->process(CallbackHandlerMustThrowException::class);
    }
}
