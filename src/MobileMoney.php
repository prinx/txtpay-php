<?php

namespace Txtpay;

use Prinx\Notify\Log;
use function Prinx\Dotenv\env;

/**
 * TXTGHANA Mobile Money Payment SDK.
 *
 * @author Kenneth Okeke <okekekenchi0802@gmail.com>
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class MobileMoney
{
    protected $primaryCallback;
    protected $secondaryCallback;
    protected $account;
    protected $network;
    protected $apiId;
    protected $apiKey;
    protected $description;
    protected $nickname;
    protected $logger;
    protected $transactionId;
    protected $voucherCode;
    protected $phone;
    protected $amount;

    public function __construct(
        $account = '',
        $network = '',
        $apiId = '',
        $apiKey = '',
        $primaryCallback = '',
        $secondaryCallback = ''
    ) {
        $this->account = $account;
        $this->network = $network;
        $this->apiId = $apiId;
        $this->apiKey = $apiKey;
        $this->primaryCallback = $primaryCallback;
        $this->secondaryCallback = $secondaryCallback;
    }

    /**
     * Automatically discover and set configurations in the .env file.
     *
     * @param string|int|double $amount
     * @param string $phone
     * @param string $network
     * @param string $voucherCode
     * 
     * @return $this
     */
    public function autoConfig(
        $amount = null,
        $phone = null,
        $network = null,
        $voucherCode = null
    ) {
        $this->setApiId(env('TXTPAY_ID'))
            ->setApiKey(env('TXTPAY_KEY'))
            ->setAccount(env('TXTPAY_ACCOUNT'))
            ->setNickname(env('TXTPAY_NICKNAME'))
            ->setDescription(env('TXTPAY_DESCRIPTION'))
            ->setPrimaryCallback(env('TXTPAY_PRIMARY_CALLBACK'))
            ->setSecondaryCallback(env('TXTPAY_SECONDARY_CALLBACK'));

        $vars = get_defined_vars();

        foreach ($vars as $name => $value) {
            if ($value) {
                $this->{'set'.ucfirst($name)}($value);
            }
        }

        return $this;
    }

    /**
     * Send Mobile Money request to the specified phone number with the specified amount.
     *
     * @param string|int|double $amount
     * @param string $phone
     * @param string $network
     * @param string $voucherCode
     * 
     * @return stdClass
     */
    public function request(
        $amount = null,
        $phone = null,
        $network = null,
        $voucherCode = null
    ) {
        $token = $this->generateToken();

        $amount = $amount ?? $this->amount;
        $network = $network ?? $this->network;
        $phone = $phone ?? $this->phone;

        foreach (compact('amount', 'network', 'phone') as $name => $value) {
            if (is_null($value)) {
                throw new \Exception('Invalid argument "'.$name.'"');       
            }
        }

        $payload = [
            'channel'            => $network,
            'primary-callback'   => $this->primaryCallback,
            'secondary-callback' => $this->secondaryCallback,
            'amount'             => $amount,
            'nickname'           => $this->nickname,
            'description'        => $this->description,
            'reference'          => $this->transactionId(),
            'recipient'          => $phone,
        ];

        if ($voucherCode = $voucherCode ?: $this->voucherCode) {
            $payload['voucher-code'] = $voucherCode;
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->getPaymentUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer '.$token,
            ],
        ]);

        $this->log("Response Mobile money request to {$phone}:");

        $result = curl_exec($ch);

        if (!$result) {
            $data = [
                'successful' => false,
                'error'      => curl_error($ch),
                'response'   => $result,
            ];

            $this->log($data);
            curl_close($ch);

            return (object) $data;
        }

        curl_close($ch);

        $response = json_decode($result);

        if (!$response) {
            $data = [
                'successful' => false,
                'error'      => 'Invalid JSON response',
                'response'   => $response,
            ];

            $this->log($data);

            return (object) $data;
        }

        $response->successful = true;
        $response->status = $response->status ?? null;
        $response->transactionId = $this->transactionId();

        $log = (array) $response;
        unset($log['developed_by']);
        $this->log($log);

        return $response;
    }

    protected function generateToken()
    {
        $payload = [
            'txtpay_api_id'  => $this->apiId,
            'txtpay_api_key' => $this->apiKey,
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->getTokenUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_HEADER         => 0,
        ]);

        $response = curl_exec($ch);

        if (!$response) {
            $error = curl_error($ch);
            $this->log('Error: '.$error);
        }

        curl_close($ch);

        $result = json_decode($response);

        if (!$result) {
            $this->log('TxtMomoService Generate Token: Invalid JSON response:');
            $this->log($response);
            throw new \Exception('TxtMomoService Generate Token: Invalid JSON response.');
        }

        if (!isset($result->data->token)) {
            $this->log('TxtMomoService Generate Token: Response not expected:');
            $this->log($response);
            throw new \Exception('TxtMomoService Generate Token: Response not expected');
        }

        $this->log('TxtMomoService Generate Token successfully.');

        return $result->data->token;
    }

    public function transactionId()
    {
        if (!isset($this->transactionId)) {
            $now = date('YmdHi');
            $beginFrom = date('YmdHi', strtotime('2009-01-10 00:00:00'));
            $range = $now - $beginFrom;
            $rand = rand(0, $range);

            $this->transactionId = strval($beginFrom + $rand);
        }

        return $this->transactionId;
    }

    public function getTokenUrl()
    {
        return 'https://txtpay.apps2.txtghana.com/api/v1/'.$this->account.'/token';
    }

    public function getPaymentUrl()
    {
        return 'http://txtpay.apps2.txtghana.com/api/v1/'.$this->account.'/payment-app/receive-money/';
    }

    public function log($data, $level = 'info')
    {
        $logger = $this->getLogger();

        if (!is_null($logger) && method_exists($logger, $level)) {
            call_user_func([$logger, $level], $data);
        }
    }

    public function getLogFile()
    {
        return $this->logFile;
    }

    public function defaultLogFile()
    {
        return realpath(__DIR__.'/../../../').'/storage/logs/txtpay/mobile-money/transaction.log';
    }

    public function setLogFile($file)
    {
        $this->logFile = $file;

        return $this;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    public function getLogger()
    {
        if (is_null($this->logFile)) {
            $this->logFile = new Log;
            $this->logFile->setFile($this->defaultLogFile());
        }

        return $this->logger;
    }

    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function setNetwork($network)
    {
        $this->network = $network;

        return $this;
    }

    public function getNetwork()
    {
        return $this->network;
    }

    public function setApiId($apiId)
    {
        $this->apiId = $apiId;

        return $this;
    }

    public function getApiId()
    {
        return $this->apiId;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getApiKey()
    {
        $this->apiKey;
    }

    public function setPrimaryCallback($callback)
    {
        $this->primaryCallback = $callback;

        return $this;
    }

    public function getPrimaryCallback()
    {
        return $this->primaryCallback;
    }

    public function setSecondaryCallback($callback)
    {
        $this->setSecondaryCallback = $callback;

        return $this;
    }

    public function getSecondaryCallback()
    {
        return $this->secondaryCallback;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setNickname($nickname)
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getNickname()
    {
        return $this->nickname;
    }

    public function setVoucherCode($voucherCode)
    {
        $this->voucherCode = $voucherCode;

        return $this;
    }

    public function getVoucherCode()
    {
        return $this->voucherCode;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhone()
    {
        return $this->phone;
    }
}
