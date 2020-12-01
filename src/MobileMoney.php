<?php

namespace Txtpay;

use Prinx\Notify\Log;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;
use Txtpay\Contracts\MobileMoneyInterface;
use function Prinx\Dotenv\env;

/**
 * TXTGHANA Mobile Money Payment SDK.
 *
 * @author Kenneth Okeke <okekekenchi0802@gmail.com>
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class MobileMoney implements MobileMoneyInterface
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
    protected $logFile;
    protected $transactionId;
    protected $voucherCode;
    protected $phone;
    protected $amount;
    protected $envCredententialsPrefix = '';
    protected $envCredententialsSuffix = '';
    protected $configured = false;

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
    public function configure(
        $amount = null,
        $phone = null,
        $network = null,
        $voucherCode = null
    ) {
        $prefix = $this->envCredententialsPrefix;
        $suffix = $this->envCredententialsSuffix;

        $this->apiId = env($prefix.'TXTPAY_ID'.$suffix);
        $this->apiKey = env($prefix.'TXTPAY_KEY'.$suffix);
        $this->account = env($prefix.'TXTPAY_ACCOUNT'.$suffix);
        $this->nickname = env($prefix.'TXTPAY_NICKNAME'.$suffix);
        $this->description = env($prefix.'TXTPAY_DESCRIPTION'.$suffix);
        $this->primaryCallback = env($prefix.'TXTPAY_PRIMARY_CALLBACK'.$suffix);
        $this->secondaryCallback = env($prefix.'TXTPAY_SECONDARY_CALLBACK'.$suffix);

        $vars = get_defined_vars();

        foreach ($vars as $name => $value) {
            if ($value) {
                $this->{$name} = $value;
            }
        }

        $this->configured = true;

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
        if (!$this->configured) {
            $this->configure();
        }

        $url = $this->getPaymentUrl();
        $headers = $this->momoRequestHeaders();
        $payload = $this->momoRequestPayload($amount, $phone, $network, $voucherCode);

        $response = $this->sendRequest($url, $payload, $headers);

        if ($response->isSuccessful) {
            $response->status = $response->content->status ?? null;
            $response->transactionId = $this->getTransactionId();
            $toLog = (array) $response;
            unset($toLog['developed_by']);
        } else {
            $toLog = (array) $response;
        }

        $this->log('Response Mobile money request to '.$payload['recipient'].':');
        $this->log($toLog);

        return $response;
    }

    public function momoRequestHeaders()
    {
        $token = $this->generateToken();

        $headers =  [
            'Authorization' => 'Bearer '.$token
        ];

        return $headers;
    }

    public function momoRequestPayload($amount, $phone, $network, $voucherCode)
    {
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
            'reference'          => $this->getTransactionId(),
            'recipient'          => $phone,
        ];

        if ($voucherCode = $voucherCode ?: $this->voucherCode) {
            $payload['voucher-code'] = $voucherCode;
        }

        return $payload;
    }

    public function generateToken()
    {
        if (!$this->configured) {
            $this->configure();
        }

        $payload = [
            'txtpay_api_id'  => $this->apiId,
            'txtpay_api_key' => $this->apiKey,
        ];
        
        $response = $this->sendRequest($this->getTokenUrl(), $payload);

        if (!$response->isSuccessful || !isset($response->content->data->token)) {
            $this->log('Error when generating token:');
            $this->log($response);

            throw new \Exception($response->error);
        }

        return $response->content->data->token;
    }

    public function sendRequest($url, $payload, $headers = [])
    {
        try {
            $client = HttpClient::create([
                'max_redirects' => 10,
            ]);

            $response = $client->request('POST', $url, [
                'json' => $payload,
                'headers' => $headers,
            ]);

            $content = $response->toArray();

            $data = [
                'isSuccessful' => true,
                'content'   => $content,
            ];
        } catch (Throwable $th) {
            $data = $this->errorResponse($th, $response);
        }

        return  json_decode(json_encode($data));
    }

    public function errorResponse(Throwable $exception, ResponseInterface $response)
    {
        $content = $response->getContent(false);
        $parsed = json_decode($content, true);
        $error = $exception->getMessage();

        if ($parsed && isset($parsed['status']) && $parsed['status'] === 400) {
            $error = $parsed['message'] ?? $error;
        }

        return [
            'isSuccessful' => false,
            'error'      => $error,
            'response'   => $parsed ?: $content,
        ];
    }

    public function getTransactionId()
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

    public function getDefaultLogFile()
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
            $this->logFile->setFile($this->getDefaultLogFile());
        }

        return $this->logger;
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

    public function getApiId()
    {
        return $this->apiId;
    }

    public function getApiKey()
    {
        return $this->apiKey;
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

    public function setEnvCredententialsPrefix(string $prefix)
    {
        $this->envCredententialsPrefix = $prefix;

        return $this;
    }
    
    public function setEnvCredententialsSuffix(string $suffix)
    {
        $this->envCredententialsSuffix = $suffix;

        return $this;
    }
}
