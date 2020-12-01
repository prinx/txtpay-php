<?php

namespace Txtpay;

use Closure;
use Prinx\Notify\Log;
use Txtpay\Contracts\CallbackInterface;
use Txtpay\Http\Request;

/**
 * TXTGHANA Payment Gateway SDK.
 *
 * @author Kenneth Okeke <okekekenchi0802@gmail.com>
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Callback implements CallbackInterface
{
    /**
     * @var Log
     */
    protected $logger;

    protected $logFolder;

    /**
     * @var array
     */
    protected $payload;

    protected $canLog = true;

    protected $jsonPrettyPrint = true;

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
    protected $failureCodes = ['101', '102', '103', '104', '114', '600', '909', 'default'];

    /**
     * If the transaction was successful.
     *
     * @var bool
     */
    protected $isSuccessful;

    /**
     * @var Closure[]
     */
    protected $callbacks = [];

    /**
     * @var Request
     */
    public $request;

    public function __construct()
    {
        $this->request = Request::capture();
        $this->handlePayload();
    }

    public function handlePayload()
    {
        $this->validatePayload();
        $this->setPayload($this->request->input->all());
        $this->logPayload();
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
        if ($this->matches($conditions)) {
            $this->register($callback);
        }

        return $this;
    }

    public function matches($conditions)
    {
        if (!is_array($conditions)) {
            $conditions = [$this->defaultConditionName => $conditions];
        }

        $payload = $this->getPayload();

        foreach ($conditions as $key => $value) {
            if (!isset($payload[$key])) {
                throw new \RuntimeException('Unknown key '.$key.' in the conditions passed to the "on" method.');
            }
  
            if ($payload[$key] != $value) {
                return false;
            }
        }

        return true;
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
        $this->registerIf($this->isSuccessful(), $callback);

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
        $this->registerIf($this->failed(), $callback);

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
     * Register the callback if the condition is met.
     *
     * @param bool|Closure $condition
     * @param Closure $callback
     * 
     * @return void
     */
    public function registerIf($condition, Closure $callback)
    {
        $mustBeRegistered = is_callable($condition) ? call_user_func($condition) : $condition;

        if ($mustBeRegistered) {
            $this->register($callback);
        }
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
            $callback = $callback->bindTo($this);

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

    public function setPayload($payload = [])
    {
        $this->originalPayload = $payload;

        $this->populateCustomPayloadNames();

        return $this;
    }

    public function populateCustomPayloadNames()
    {
        foreach ($this->customPayloadNames as $original => $custom) {
            $this->payload[$custom] = $this->originalPayload[$original];
        }

        return $this->payload;
    }

    public function getPayload($attribute = null)
    {
        return $attribute ? $this->payload[$attribute] ?? null : $this->payload;
    }

    public function validatePayload()
    {
        $payload = $this->request->input->all();

        foreach ($this->requiredParameters as $param) {
            if (!isset($payload[$param])) {
                $this->log(
                    "Missing parameters \"{$param}\" in the request.\n".
                    'Payload: '.$this->jsonEncode($payload),
                    $this->failureLogFile()
                );

                exit('Some parameters are missing in the request payload.');
            }
        }
    }

    public function logPayload()
    {
        $this->log(
            'Callback Received for momo transaction to '.$this->getPayload('phone').
            "\nPayload: ".$this->jsonEncode($this->getPayload()),
            $this->callbackLogFile()
        );
    }

    public function getLogger()
    {
        return $this->logger ?? $this->logger = new Log;
    }

    public function log($message, $file = '', $level = 'info')
    {
        if ($this->canLog) {
            SlackLog::log($message, $level);

            if ($file && $this->getLogger()) {
                $this->getLogger()
                    ->setFile($file)
                    ->{$level}($message);
            }
        }
    }

    public function isSuccessful()
    {
        if (is_null($this->isSuccessful)) {
            $this->isSuccessful = in_array(
                $this->getPayload($this->defaultConditionName),
                $this->successCodes
            );
        }

        return $this->isSuccessful;
    }

    public function failed()
    {
        return !$this->isSuccessful();
    }

    public static function getMessages($code = null, $transactionId = null)
    {
        $messages = [
            '000'     => 'Transaction successful. Your transaction ID is '.$transactionId,
            '101'     => 'Transaction failed. Insufficient fund in wallet.',
            '102'     => 'Transaction failed. Number non-registered for mobile money.',
            '103'     => 'Transaction failed. Wrong PIN. Transaction timed out.',
            '104'     => 'Transaction failed. Transaction declined',
            '114'     => 'Transaction failed. Invalid voucher',
            '600'     => 'Transaction failed. Can not process request',
            '909'     => 'Transaction failed. Duplicate transaction id.',
            'default' => 'Transaction failed.',
        ];

        return $code ? $messages[$code] ?? $messages['default'] : $messages;
    }

    public function getMessage()
    {
        return $this->getMessages($this->getPayload('code'), $this->getPayload('id'));
    }

    public function respond($message)
    {
        echo $message;
    }

    public function callbackLogFile()
    {
        return $this->logFolder('callback.log');
    }

    public function successLogFile()
    {
        return $this->logFolder('success.log');
    }

    public function failureLogFile()
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

    public function getId()
    {
        return $this->getPayload('id');
    }

    public function getPhone()
    {
        return $this->getPayload('phone');
    }

    public function getAmount()
    {
        return $this->getPayload('amount');
    }

    public function getCode()
    {
        return $this->getPayload('code');
    }

    public function getStatus()
    {
        return $this->getPayload('status');
    }

    public function getDetails()
    {
        return $this->getPayload('details');
    }

    public function getNetwork()
    {
        return $this->getPayload('network');
    }

    public function getCurrency()
    {
        return $this->getPayload('currency');
    }

    public function setCanLog(bool $canLog)
    {
        $this->canLog = $canLog;
        
        return $this;
    }

    public function disableLog()
    {
        return $this->setCanLog(false);
    }

    public function enableLog()
    {
        return $this->setCanLog(true);
    }

    public function jsonEncode($value, $options = 0, $depth = 512)
    {
        if ($this->jsonPrettyPrint) {
            return json_encode($value, JSON_PRETTY_PRINT|$options, $depth);
        }

        return json_encode($value, $options, $depth);
    }

    public function setJsonPrettyPrint(bool $pretty)
    {
        $this->jsonPrettyPrint = $pretty;

        return $this;
    }
}
