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

use function Prinx\Dotenv\env;
use function Prinx\Dotenv\persistEnv;
use Tests\TestCase;
use Txtpay\MobileMoney;

class MobileMoneyTest extends TestCase
{
    protected $defaultConfig = [
        'TXTPAY_ID'                 => 'your_txtpay_id',
        'TXTPAY_KEY'                => 'your_txtpay_key',
        'TXTPAY_ACCOUNT'            => 'your_txtpay_account',
        'TXTPAY_NICKNAME'           => 'your_txtpay_nickname',
        'TXTPAY_DESCRIPTION'        => 'your_txtpay_description',
        'TXTPAY_PRIMARY_CALLBACK'   => 'primary_callback',
        'TXTPAY_SECONDARY_CALLBACK' => 'secondary_callback',
    ];

    public function testAutoConfig()
    {
        $this->runConfigTest();
    }

    public function testConfigWithPrefix()
    {
        $this->runConfigTest('PREFIX_');
    }

    public function testConfigWithSuffix()
    {
        $this->runConfigTest('', '_SUFFIX');
    }

    public function testConfigWithPrefixAndSuffix()
    {
        $this->runConfigTest('PREFIX_', '_SUFFIX');
    }

    /**
     * Test make request.
     * To run this test kindly update the .env.bis file true api credentials.
     *
     * @return void
     */
    public function testMakeRequest()
    {
        $this->loadEnv(realpath(__DIR__.'/../../').'/.env.bis');

        $payment = new MobileMoney;

        $amount = 0.2;
        $phone = env('TEST_PHONE');
        $network = 'MTN';

        $request = $payment->request($amount, $phone, $network);

        // dump($request);
        $this->assertTrue($request->isSuccessful);
        $this->assertEquals($request->transactionId, $payment->getTransactionId());
    }

    public function runConfigTest($prefix = '', $suffix = '')
    {
        $this->loadEnv(realpath(__DIR__.'/../../').'/.env');

        $this->fillEnvWithConfig($prefix, $suffix);

        $payment = new MobileMoney;

        $payment->configure();
        $this->assertEquals($payment->getApiId(), env($prefix.'TXTPAY_ID'.$suffix));
        $this->assertEquals($payment->getApiKey(), env($prefix.'TXTPAY_KEY'.$suffix));
        $this->assertEquals($payment->getAccount(), env($prefix.'TXTPAY_ACCOUNT'.$suffix));
        $this->assertEquals($payment->getNickname(), env($prefix.'TXTPAY_NICKNAME'.$suffix));
        $this->assertEquals($payment->getDescription(), env($prefix.'TXTPAY_DESCRIPTION'.$suffix));
        $this->assertEquals($payment->getPrimaryCallback(), env($prefix.'TXTPAY_PRIMARY_CALLBACK'.$suffix));
        $this->assertEquals($payment->getSecondaryCallback(), env($prefix.'TXTPAY_SECONDARY_CALLBACK'.$suffix));
    }

    public function fillenvWithConfig($prefix = '', $suffix = '')
    {
        $env = realpath(__DIR__.'/../../').'/.env';
        $this->createEnvIfNotExist($env);

        foreach ($this->defaultConfig as $key => $value) {
            persistEnv($prefix.$key.$suffix, $value);
        }
    }

    public function createEnvIfNotExist($path)
    {
        if (!file_exists($path)) {
            file_put_contents($path, '');
        }
    }

    public function loadEnv($env)
    {
        $this->createEnvIfNotExist($env);

        loadEnv($env);
    }
}
