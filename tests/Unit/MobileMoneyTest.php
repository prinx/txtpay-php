<?php

namespace Tests\Unit;

use Tests\TestCase;
use Txtpay\MobileMoney;
use function Prinx\Dotenv\env;
use function Prinx\Dotenv\persistEnv;

class MobileMoneyTest extends TestCase
{
    protected $defaultConfig = [
        'TXTPAY_ID' => 'your_txtpay_id',
        'TXTPAY_KEY' => 'your_txtpay_key',
        'TXTPAY_ACCOUNT' => 'your_txtpay_account',
        'TXTPAY_NICKNAME' => 'your_txtpay_nickname',
        'TXTPAY_DESCRIPTION' => 'your_txtpay_description',
        'TXTPAY_PRIMARY_CALLBACK' => 'primary_callback',
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

    public function runConfigTest($prefix = '', $suffix = '')
    {
        $this->fillEnvWithConfig($prefix, $suffix);

        $payment = new MobileMoney;

        $payment->configure();
        dump($payment->getApiKey());
        // dump(env($prefix.'TXTPAY_KEY'.$suffix));
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
        $env = realpath(__DIR__.'/../../').'.env';
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
}
