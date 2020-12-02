<?php

namespace Txtpay\Contracts;

use Txtpay\Callback;

interface CallbackHandlerInterface
{
    public function callbacks(Callback $callback);
}
