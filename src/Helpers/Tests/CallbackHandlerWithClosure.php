<?php

namespace Txtpay\Helpers\Tests;

use Txtpay\Callback;
use Txtpay\Contracts\CallbackHandlerInterface;

class CallbackHandlerWithClosure implements CallbackHandlerInterface
{
    public function callbacks(Callback $callback)
    {
        return [
            ['000', function (Callback $callback) { echo '__000__'; }],
            ['101', function (Callback $callback) { echo '__101__'; }],
            ['102', function (Callback $callback) { echo '__102__'; }],
            ['103', function (Callback $callback) { echo '__103__'; }],
            ['104', function (Callback $callback) { echo '__104__'; }],
            ['114', function (Callback $callback) { echo '__114__'; }],
            ['600', function (Callback $callback) { echo '__600__'; }],
            ['909', function (Callback $callback) { echo '__909__'; }],
            ['success', function (Callback $callback) { echo '__Success__'; }],
            ['failure', function (Callback $callback) { echo '__Failure__'; }],
        ];
    }
}
