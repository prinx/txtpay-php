<?php

namespace Tests\Unit;

use Tests\TestCase;
use Txtpay\Callback;
use Txtpay\MobileMoney;
use function Prinx\Dotenv\env;

class CallbackScriptTest extends TestCase
{
    public function testMustReturnCorrectFeedbackMessageAccordingToRequest()
    {
        $momoService = new MobileMoney;
        $callback = new Callback;

        $messages = $callback->getMessages(null, $momoService->getTransactionId());

        $testNumber = '23354545454545';

        foreach ($messages as $code => $expectedMessage) {
            $_REQUEST = [
                'code'              => $code,
                'status'            => 'test',
                'reason'            => 'test',
                'transaction_id'    => $momoService->getTransactionId(),
                'r_switch'          => 'test',
                'subscriber_number' => $testNumber,
                'amount'            => 'test',
                'currency'          => 'test',
            ];

            $message = require Path::toPublic('transactionstatus.php');

            $this->assertEquals($message, $expectedMessage);
        }

        $this->assertFileExists($callback->callbackLog());
        $this->assertFileExists($callback->successLog());
        $this->assertFileExists($callback->failureLog());

        $callback->logger()->setFile($callback->callbackLog())->remove();
        $callback->logger()->setFile($callback->successLog())->remove();
        $callback->logger()->setFile($callback->failureLog())->remove();
    }
}
