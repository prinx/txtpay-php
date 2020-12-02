<?php

namespace Txtpay\Helpers\Tests;

use Txtpay\Callback;
use Txtpay\Contracts\CallbackHandlerInterface;

class CallbackHandlerWithCallbackInArray implements CallbackHandlerInterface
{
    public function callbacks(Callback $callback)
    {
        return [
            ['000', ['handle000', 'handle000']],
            ['101', ['handle101', 'handle101']],
            ['102', ['handle102', 'handle102']],
            ['103', ['handle103', 'handle103']],
            ['104', ['handle104', 'handle104']],
            ['114', ['handle114', 'handle114']],
            ['600', ['handle600', 'handle600']],
            ['909', ['handle909', 'handle909']],
            ['success', ['success', 'success']],
            ['failure', ['failure', 'failure']],
        ];
    }

    public function handle000(Callback $callback)
    {
        echo '__000__';
    }

    public function handle101(Callback $callback)
    {
        echo '__101__';
    }

    public function handle102(Callback $callback)
    {
        echo '__102__';
    }

    public function handle103(Callback $callback)
    {
        echo '__103__';
    }

    public function handle104(Callback $callback)
    {
        echo '__104__';
    }

    public function handle114(Callback $callback)
    {
        echo '__114__';
    }

    public function handle600(Callback $callback)
    {
        echo '__600__';
    }

    public function handle909(Callback $callback)
    {
        echo '__909__';
    }

    public function success(Callback $callback)
    {
        echo '__Success__';
    }

    public function failure(Callback $callback)
    {
        echo '__Failure__';
    }
}
