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

    protected $defaultRequiredParameter = 'code';

    protected $successCodes = ['000'];

    protected $failureCodes = ['101', '102', '103', '104', '114', '909', 'default'];

    public function __construct()
    {
        $this->captureRequest();
    }

    /**
     * Add expected parameter from callback API.
     *
     * @param string|array $param
     * 
     * @return $this
     */
    public function addRequiredParameter($param)
    {
        if (is_string($param)) {
            $this->requiredParameters[] = $param;
        } else {
            $this->requiredParameters = array_merge($this->requiredParameters, $param);
        }

        return $this;
    }

    /**
     * Set required expected.
     *
     * @param array $params
     * 
     * @return $this
     */
    public function setRequiredParameter($params)
    {
        $this->requiredParameters = $params;

        return $this;
    }

    /**
     * Run the callback if parameters match the request parameters.
     *
     * @param string|array $params String or associative array matching the request parameters.
     *                             If string, the parameter is the defaultRequiredParamter.
     * @param Closure $callback
     * 
     * @return $this
     */
    public function on($params, Closure $callback)
    {
        if (is_string($params)) {
            $params = [$this->defaultRequiredParameter => $params];
        }

        $payload = $this->getPayload();
        $match = true;

        foreach ($params as $key => $value) {
            if (!isset($payload[$key]) || $payload[$key] != $value) {
                $match = false;
                break;
            }
        }

        if ($match) {
            call_user_func($callback, $payload, $this);
        }

        return $this;
    }

    /**
     * Run callback if the transaction is successful.
     * 
     * The successful request is determined by the code of the request.
     *
     * @param Closure $callback
     * 
     * @return $this
     */
    public function success(Closure $callback)
    {
        if (in_array($this->getPayload($this->defaultRequiredParameter), $this->succesCodes)) {
            call_user_func($callback, $this->getPayload(), $this);
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
        if (in_array($this->getPayload($this->defaultRequiredParameter), $this->failureCodes)) {
            call_user_func($callback, $this->getPayload(), $this);
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
        call_user_func($callback, $this->getPayload(), $this);

        return $this;
    }

    /**
     * Add status codes.
     *
     * @param string|string[] $code Code or array of codes
     * 
     * @return $this
     */
    public function addSuccessCode($code)
    {
        if (!is_array($code)) {
            $code = [$code];
        }

        $this->succesCodes = array_unique(array_merge($this->succesCodes, $code));

        $this->failureCodes = $this->removeFromArray($this->failureCodes, $code);

        return $this;
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
     * Set failure code.
     *
     * @param string|string[] $code Code or array of codes
     * 
     * @return $this
     */
    public function addFailureCode($code)
    {
        if (!is_array($code)) {
            $code = [$code];
        }

        $this->failureCodes = array_unique(array_merge($this->failureCodes, $code));

        $this->successCodes = $this->removeFromArray($this->successCodes, $code);

        return $this;
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

    /**
     * Remove value(s) from array.
     *
     * @param array $array
     * @param string|array $toRemove
     * 
     * @return array
     */
    public function removeFromArray($array, $toRemove)
    {
        $toRemove = is_string($toRemove) ? [$toRemove] : $toRemove;

        return array_diff($array, $toRemove);
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

    public function respond($message)
    {
        echo $message;
    }

    public function getPayload($attribute = null)
    {
        return $attribute ? $this->payload[$attribute] ?? $this->originalPayload[$attribute] ?? null : $this->payload;
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
            $this->logFolder = realpath(__DIR__.'/../../../').'storage/logs/txtpay/mobile-money/'.$append;
        }

        return $this->logFolder;
    }

    public function setLogFolder($folder)
    {
        $this->logFolder = $folder;
        
        return $this;
    }
}
