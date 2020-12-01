<?php

namespace Tests\Unit;

use Tests\TestCase;
use Txtpay\Callback;
use Txtpay\MobileMoney;
use Txtpay\Support\Combination;
use function Prinx\Dotenv\env;

class CallbackTest extends TestCase
{
    public function testPayloadNamesCustomization()
    {
        $momoService = new MobileMoney;
        $payload = $_POST = [
            'code'              => '000',
            'status'            => 'test',
            'reason'            => 'test',
            'transaction_id'    => $momoService->getTransactionId(),
            'r_switch'          => 'test',
            'subscriber_number' => '23354545454545',
            'amount'            => 1,
            'currency'          => 'GHS',
        ];

        $callback = new Callback;

        $this->assertEquals($callback->getPayload('code'), $payload['code']);
        $this->assertEquals($callback->getPayload('code'), $callback->getCode());

        $this->assertEquals($callback->getPayload('status'), $payload['status']);
        $this->assertEquals($callback->getPayload('status'), $callback->getStatus());

        $this->assertEquals($callback->getPayload('details'), $payload['reason']);
        $this->assertEquals($callback->getPayload('details'), $callback->getDetails());

        $this->assertEquals($callback->getPayload('id'), $payload['transaction_id']);
        $this->assertEquals($callback->getPayload('id'), $callback->getId());

        $this->assertEquals($callback->getPayload('network'), $payload['r_switch']);
        $this->assertEquals($callback->getPayload('network'), $callback->getNetwork());

        $this->assertEquals($callback->getPayload('phone'), $payload['subscriber_number']);
        $this->assertEquals($callback->getPayload('phone'), $callback->getPhone());

        $this->assertEquals($callback->getPayload('amount'), $payload['amount']);
        $this->assertEquals($callback->getPayload('amount'), $callback->getAmount());

        $this->assertEquals($callback->getPayload('currency'), $payload['currency']);
        $this->assertEquals($callback->getPayload('currency'), $callback->getCurrency());
    }

    public function testMustRunProvidedCallbacksIfConditionsMatch()
    {
        $testNumber = '23354545454545';
        $momoService = new MobileMoney;
        $messages = Callback::getMessages(null, $momoService->getTransactionId());

        $_POST = [
            'status'            => 'test',
            'reason'            => 'test',
            'transaction_id'    => $momoService->getTransactionId(),
            'r_switch'          => 'test',
            'subscriber_number' => $testNumber,
            'amount'            => 1,
            'currency'          => 'GHS',
        ];
        
        foreach ($messages as $code => $expectedMessage) {
            $_POST['code'] = $code;

            $callback = new Callback;
            $callback->on($code, $this->callbackFunction($code));
            $callback->success($this->callbackFunction($code));

            $conditions = Combination::combine($callback->getPayload());

            foreach ($conditions as $condition) {
                $callback->on($condition, $this->callbackFunction($code));
            }

            $callback->process();
        }
    }

    public function callbackFunction($type)
    {
        $test = $this;
    
        return function ($payload, $callback) use ($test, $type) {
            $test->assertTrue(is_array($payload), "Inject payload must be an array in the success callback in closure of type/code {$type}");
            $test->assertTrue($callback instanceof Callback, "Inject callback must be the callback instance in closure of type/code {$type}");
            $test->assertSame($this, $callback, "\$this must be equal to \$callback in closure of type/code {$type}");
            $test->assertEquals($payload, $callback->getPayload(), "Invalid payload passed to closure of type/code {$type}");
        };
    }
}
