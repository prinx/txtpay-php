<?php

namespace Txtpay\Helpers\Tests;

use Txtpay\Callback;
use Txtpay\Contracts\CallbackHandlerInterface;

class CallbackHandlerMustThrowException implements CallbackHandlerInterface
{
    public function callbacks(Callback $callback)
    {
        return [
            ['000', 'unknownMethod'],
        ];
    }
}
