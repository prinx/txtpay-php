<?php

namespace Txtpay\Callback;

use Txtpay\Callback;
use Txtpay\Contracts\CallbackHandlerInterface;

class Handler implements CallbackHandlerInterface
{
    public function callbacks(Callback $callback)
    {
        return [
            // ['000', ''],
            // ['success', ''],
            // ['failure', ''],
        ];
    }
}
