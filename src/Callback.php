<?php

namespace Txtpay;

use Closure;
use Prinx\Notify\Log;

/**
 * TXTGHANA Payment Gateway SDK.
 *
 * @author Kenneth Okeke <okekekenchi0802@gmail.com>
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Callback
{
    /**
     * Logger.
     *
     * @var Log
     */
    protected $logger;

    protected $payload;

    protected $requiredParameters = [
        'code',
        'status',
        'reason',
        'transaction_id',
        'r_switch',
        'subscriber_number',
        'amount',
        'currency',
    ];

    protected $customPayloadNames = [
        'code'              => 'code',
        'status'            => 'status',
        'reason'            => 'details',
        'transaction_id'    => 'id',
        'r_switch'          => 'network',
        'subscriber_number' => 'phone',
        'amount'            => 'amount',
        'currency'          => 'currency'
    ];

    protected $defaultConditionName = 'code';

    /**
     * Codes of the request payload that determines that the transaction was successful.
     * 
     * @var array
     */
    protected $successCodes = ['000'];

    /**
     * Codes of the request payload that determines that the transaction failed.
     *
     * @var array
     */
    protected $failureCodes = ['101', '102', '103', '104', '114', '909', 'default'];

    /**
     * If the transaction was successful.
     *
     * @var bool
     */
    protected $successful;

    /**
     * Callbacks
     *
     * @var array
     */
    protected $callbacks = [];

    public function __construct()
    {
        $this->captureRequest();
    }

    /**
     * Register the callback if conditions match the request parameters.
     *
     * @param string|array $conditions String or associative array matching the request parameters.
     *                             If string, the parameter is the defaultConditionName.
     * @param Closure $callback
     * 
     * @return $this
     */
    public function on($conditions, Closure $callback)
    {
        if (is_string($conditions)) {
            $conditions = [$this->defaultConditionName => $conditions];
        }

        $payload = $this->getPayload();
        $match = true;

        foreach ($conditions as $key => $value) {
            if (!isset($payload[$key])) {
                throw new \RuntimeException('Unknown key '.$key.' in the conditions passed to the "on" method.');
            }
  
            if ($payload[$key] != $value) {
                $match = false;
                break;
            }
        }

        if ($match) {
            $this->register($callback);
        }

        return $this;
    }

    /**
     * Register callback if the transaction is successful.
     * 
     * The successful transaction is determined by the code of the request.
     *
     * @param Closure $callback
     * 
     * @return $this
     */
    public function success(Closure $callback)
    {
        if (in_array($this->getPayload($this->defaultConditionName), $this->successCodes)) {
            $this->register($callback);
        }

        return $this;
    }

    /**
     * Run callback if the transaction has failed.
     * 
     * The failed request is determined by the code of the request.
     *
     * @param Closure $callback
     * 
     * @return $this
     */
    public function failure(Closure $callback)
    {
        if (in_array($this->getPayload($this->defaultConditionName), $this->failureCodes)) {
            $this->register($callback);
        }

        return $this;
    }

    /**
     * Run callback whether the transaction is successful or not.
     *
     * @param Closure $callback
     * 
     * @return $this
     */
    public function always(Closure $callback)
    {
        return $this->register($callback);
    }

    /**
     * Register callback.
     *
     * @param Closure $callback
     * 
     * @return $this
     */
    public function register(Closure $callback)
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    /**
     * Run the registered callbacks against the callback request.
     *
     * @param Closure $callback Optional callback that will be run after all the callbacks have been run.
     * 
     * @return $this
     */
    public function process(Closure $callback = null)
    {
        if ($callback) {
            $this->register($callback);
        }

        $this->runCallbacks();

        return $this;
    }

    public function runCallbacks()
    {
        foreach ($this->callbacks as $callback) {
            call_user_func_array($callback, [$this->getPayload(), $this]);
        }
    }

    /**
     * Success codes.
     *
     * @return array
     */
    public function getSuccessCodes()
    {
        return $this->successCodes;
    }

    /**
     * Failure codes.
     *
     * @return array
     */
    public function getFailureCodes()
    {
        return $this->failureCodes;
    }

    public function captureRequest()
    {
        /*
         * @see https://stackoverflow.com/a/11990821/14066311
         */
        $json = file_get_contents('php://input');
        $payload = (array) json_decode($json, true);

        $this->validatePayload($payload);
        $this->setPayload($payload);
        $this->logPayload();

        return $this;
    }

    public function setPayload($payload = [])
    {
        $this->originalPayload = array_replace($payload, $_REQUEST);

        foreach ($this->customPayloadNames as $original => $custom) {
            $this->payload[$custom] = $this->originalPayload[$original];
        }

        return $this;
    }

    public function getPayload($attribute = null)
    {
        return $attribute ? $this->payload[$attribute] ?? $this->originalPayload[$attribute] ?? null : $this->payload;
    }

    public function validatePayload($payload)
    {
        foreach ($this->requiredParameters as $param) {
            if (!isset($payload[$param])) {
                $this->log(
                    "Missing parameters \"{$param}\" in the request.\n".
                    'Payload: '.json_encode($payload, JSON_PRETTY_PRINT),
                    $this->failureLog()
                );

                exit('Some parameters are missing in the request payload.');
            }
        }
    }

    public function logPayload()
    {
        $this->log(
            'Callback Received for momo transaction to '.$this->getPayload('phone').
            "\nPayload: ".json_encode($this->getPayload(), JSON_PRETTY_PRINT),
            $this->callbackLog()
        );
    }

    public function logger()
    {
        return $this->logger ?? $this->logger = new Log;
    }

    public function log($message, $file = '', $level = 'info')
    {
        if ($file && $this->logger()) {
            $this->logger()
                ->setFile($file)
                ->{$level}($message);
        }

        SlackLog::log($message, $level);
    }

    public function successful()
    {
        if (is_null($this->successful)) {
            $this->successful = in_array(
                $this->getPayload($this->defaultConditionName),
                $this->successCodes
            );
        }

        return $this->successful;
    }

    public function failed()
    {
        return !$this->successful();
    }

    public function messages($code = null, $transactionId = null)
    {
        $messages = [
            '000'     => 'Transaction successful. Your transaction ID is '.$transactionId,
            '101'     => 'Transaction failed. Insufficient fund in wallet.',
            '102'     => 'Transaction failed. Number non-registered for mobile money.',
            '103'     => 'Transaction failed. Wrong PIN. Transaction timed out.',
            '104'     => 'Transaction failed. Transaction declined',
            '114'     => 'Transaction failed. Invalid voucher',
            '909'     => 'Transaction failed. Duplicate transaction id.',
            'default' => 'Transaction failed.',
        ];

        return $code ? $messages[$code] ?? $messages['default'] : $messages;
    }

    public function message()
    {
        return $this->messages($this->getPayload('code'), $this->getPayload('id'));
    }

    public function respond($message)
    {
        echo $message;
    }

    public function callbackLog()
    {
        return $this->logFolder('callback.log');
    }

    public function successLog()
    {
        return $this->logFolder('success.log');
    }

    public function failureLog()
    {
        return $this->logFolder('error.log');
    }

    public function logFolder($append = '')
    {
        if (is_null($this->logFolder)) {
            $this->logFolder = realpath(__DIR__.'/../../../').'/storage/logs/txtpay/mobile-money/callback/'.$append;
        }

        return $this->logFolder;
    }

    public function setLogFolder($folder)
    {
        $this->logFolder = $folder;
        
        return $this;
    }

    public function id()
    {
        return $this->getPayload('id');
    }

    public function phone()
    {
        return $this->getPayload('phone');
    }

    public function amount()
    {
        return $this->getPayload('amount');
    }

    public function code()
    {
        return $this->getPayload('code');
    }

    public function status()
    {
        return $this->getPayload('status');
    }

    public function details()
    {
        return $this->getPayload('details');
    }

    public function network()
    {
        return $this->getPayload('network');
    }

    public function currency()
    {
        return $this->getPayload('currency');
    }
}
