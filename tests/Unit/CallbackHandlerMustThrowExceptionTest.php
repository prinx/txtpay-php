<?php

namespace Tests\Unit;

use Tests\TestCase;
use Txtpay\Callback;
use Txtpay\Exceptions\InvalidHandlerException;
use Txtpay\Helpers\Tests\CallbackHandlerMustThrowException;
use Txtpay\MobileMoney;

class CallbackHandlerMustThrowExceptionTest extends TestCase
{
    public function testCallbackHandlerMustThrowInvalidHandlerException()
    {
        $this->expectException(InvalidHandlerException::class);

        $_POST = [
            'code'            => '000',
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
