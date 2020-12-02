<?php

/*
 * This file is part of the Txtpay package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Txtpay\Helpers\Tests;

use Txtpay\Callback;
use Txtpay\Contracts\CallbackHandlerInterface;

class CallbackHandlerWithConditionInArray implements CallbackHandlerInterface
{
    public function callbacks(Callback $callback)
    {
        return [
            [['code' => '000'], 'handle000'],
            [['code' => '101'], 'handle101'],
            [['code' => '102'], 'handle102'],
            [['code' => '103'], 'handle103'],
            [['code' => '104'], 'handle104'],
            [['code' => '114'], 'handle114'],
            [['code' => '600'], 'handle600'],
            [['code' => '909'], 'handle909'],
            ['success', 'success'],
            ['failure', 'failure'],
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
