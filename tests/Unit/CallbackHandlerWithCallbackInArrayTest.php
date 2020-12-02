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
use Txtpay\Helpers\Tests\CallbackHandlerWithCallbackInArray;
use Txtpay\MobileMoney;

class CallbackHandlerWithCallbackInArrayTest extends TestCase
{
    public function testMustRunProvidedCallbacksIfConditionsMatchFromCallbackHandlerClass()
    {
        $this->loadEnv(realpath(__DIR__.'/../../').'/.env');
        
        $id = (new MobileMoney)->getTransactionId();
        $messages = Callback::getMessages(null, $id);

        $_POST = [
            'status'            => 'test',
            'reason'            => 'test',
            'transaction_id'    => $id,
            'r_switch'          => 'test',
            'subscriber_number' => '23354545454545',
            'amount'            => 1,
            'currency'          => 'GHS',
        ];

        foreach ($messages as $code => $expectedMessage) {
            if ($code === 'default') {
                continue;
            }

            $_POST['code'] = $code;

            $callback = new Callback;
            ob_start();
            $callback->process(CallbackHandlerWithCallbackInArray::class);
            $echoed = ob_get_clean();

            if ($callback->isSuccessful()) {
                $this->assertStringContainsString('__Success____Success__', $echoed);
            } elseif ($callback->failed()) {
                $this->assertStringContainsString('__Failure____Failure__', $echoed);
            }

            $this->assertStringContainsString('__'.$code.'____'.$code.'__', $echoed);
        }
    }
}
