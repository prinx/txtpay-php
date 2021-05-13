<?php

/*
 * This file is part of the Txtpay package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Txtpay;

use Prinx\Notify\Log;
use Txtpay\Contracts\CallbackInterface;
use Txtpay\Exceptions\CallbackClassNotFoundException;
use Txtpay\Exceptions\InvalidCallbackClassException;
use Txtpay\Exceptions\InvalidCallbackHandlerException;
use Txtpay\Exceptions\InvalidPayloadKeyException;
use Txtpay\Exceptions\UndefinedCallbackBagException;
use Txtpay\Http\Request;
use Txtpay\Support\SlackLog;

/**
 * TXTGHANA Payment Gateway SDK.
 *
 * @author Kenneth Okeke <okekekenchi0802@gmail.com>
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Callback implements CallbackInterface
{
    const HANDLER_CONDITION = 0;
    const HANDLER_CALLBACK = 1;

    protected $customConditions = [
        'success',
        'failure',
        'always',
    ];

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
        'currency'          => 'currency',
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
     * @var callable[]
     */
    protected $callbacks = [];

    protected $handler;

    protected $callbackBagName = 'callbacks';

    /**
     * @var Request
     */
    public $request;

    public function __construct()
    {
        $this->request = Request::capture();
        $this->handlePayload();
    }

    private function handlePayload()
    {
        $this->validatePayload();
        $this->setPayload($this->request->input->all());
        $this->logPayload();
    }

    /**
     * Register the callback if conditions match the request parameters.
     *
     * @param string|array    $condition String or associative array matching the request parameters.
     *                                   If string, the parameter is either one of the custom conditions
     *                                   specified or the defaultConditionName.
     * @param callable|string $callback  callable or name of the method in the callback handler class.
     *
     * @return $this
     */
    public function on($condition, $callback)
    {
        if ($this->isCustomCondition($condition)) {
            return $this->{$condition}($callback);
        }

        if ($this->matches($condition)) {
            return $this->register($callback);
        }

        return $this;
    }

    public function isCustomCondition($condition)
    {
        return in_array($condition, $this->customConditions);
    }

    /**
     * Check if the request payload matches a condition.
     *
     * @param array|string $condition
     *
     * @return bool
     */
    public function matches($condition)
    {
        if (!is_array($condition)) {
            $condition = [$this->defaultConditionName => $condition];
        }

        $payload = $this->getPayload();

        foreach ($condition as $key => $value) {
            if (!isset($payload[$key])) {
                throw new InvalidPayloadKeyException('Unknown key '.$key.' in the conditions passed to the "on" method.');
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
     * @param callable|string $callback
     *
     * @return $this
     */
    public function success($callback)
    {
        $this->registerIf($this->isSuccessful(), $callback);

        return $this;
    }

    /**
     * Run callback if the transaction has failed.
     *
     * The failed request is determined by the code of the request.
     *
     * @param callable|string $callback
     *
     * @return $this
     */
    public function failure($callback)
    {
        $this->registerIf($this->failed(), $callback);

        return $this;
    }

    /**
     * Run callback whether the transaction is successful or not.
     *
     * @param callable|string $callback
     *
     * @return $this
     */
    public function always($callback)
    {
        return $this->register($callback);
    }

    /**
     * Register callback.
     *
     * @param callable|string $callback
     *
     * @return $this
     */
    public function register($callback)
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    /**
     * Register the callback if the condition is met.
     *
     * @param bool|callable   $condition
     * @param callable|string $callback
     *
     * @return void
     */
    public function registerIf($condition, $callback)
    {
        $mustBeRegistered = is_callable($condition) ? call_user_func($condition) : $condition;

        if ($mustBeRegistered) {
            $this->register($callback);
        }
    }

    /**
     * Run the registered callbacks against the callback request.
     *
     * @param callable|string|object $callback If a callable is passed, this callback will be added
     *                                         to the callbacks stack and will be run after all the
     *                                         callbacks have been run.
     *                                         If a string or an object is passed, it will be
     *                                         considered as a class containing the callback
     *                                         handling methods.
     *
     * @return $this
     */
    public function process($callback = null)
    {
        if ($callback) {
            $this->registerProcessCallback($callback);
        }

        $this->runCallbacks();

        return $this;
    }

    /**
     * Register callback passed to the `process` method.
     *
     * @param callable|object|string $callback
     *
     * @throws CallbackClassNotFoundException
     * @throws InvalidCallbackClassException
     *
     * @return $this
     */
    private function registerProcessCallback($callback)
    {
        if (is_callable($callback)) {
            return $this->register($callback);
        }

        if (class_exists($callback) || is_object($callback)) {
            return $this->registerFromClass($callback);
        }

        if (is_string($callback)) {
            throw new CallbackClassNotFoundException('Class '.$callback.' not found.');
        }

        throw new InvalidCallbackClassException('Invalid parameter passed to "process" method. Callable, classname or object expected.');
    }

    private function registerFromClass($handler)
    {
        $this->setCallbackHandler($handler);

        $bag = $this->getCallbackBagFromHandler();

        foreach ($bag as $bagItem) {
            $this->on(...$this->getRegistrationParams($bagItem));
        }
    }

    public function setCallbackHandler($handler)
    {
        if (is_string($handler)) {
            $handler = new $handler();
        }

        if (!is_object($handler)) {
            throw new InvalidCallbackHandlerException('The callback handler must be a classname or an object');
        }

        $this->handler = $handler;

        return $this;
    }

    private function getRegistrationParams($callback, $handler = null)
    {
        $handler = $handler ?: $this->handler;

        $condition = $callback[self::HANDLER_CONDITION];
        $methods = $callback[self::HANDLER_CALLBACK];
        $methods = is_array($methods) ? $methods : [$methods];

        foreach ($methods as $method) {
            if (is_string($method) && !method_exists($this->handler, $method)) {
                $condition = is_string($condition) ? $condition : json_encode($condition);

                throw new InvalidCallbackHandlerException('Method "'.$method.'" expected by condition '.$condition.' is not found in the handler class "'.get_class($this->handler).'"');
            }
        }

        return [$condition, $methods];
    }

    private function getCallbackBagFromHandler($handler = null)
    {
        $handler = $handler ?: $this->handler;

        if (method_exists($handler, $this->callbackBagName)) {
            return call_user_func([$handler, $this->callbackBagName], $this);
        }

        if (property_exists($handler, $this->callbackBagName)) {
            return $handler->{$this->callbackBagName};
        }

        throw new UndefinedCallbackBagException('The callback handler class must contain a method or a property "'.$this->callbackBagName.'"');
    }

    private function runCallable($callable)
    {
        return call_user_func_array($callable, [$this]);
    }

    private function runCallbacks()
    {
        foreach ($this->callbacks as $callback) {
            if (is_callable($callback)) {
                $this->runCallable($callback);
                continue;
            }

            if (is_array($callback)) {
                foreach ($callback as $actualCallback) {
                    if (is_callable($actualCallback)) {
                        $this->runCallable($actualCallback);
                    } elseif (is_object($this->handler)) {
                        call_user_func_array([$this->handler, $actualCallback], [$this]);
                    }
                }

                continue;
            }

            if ((is_string($callback)) && is_object($this->handler)) {
                $callback = is_string($callback) ? [$callback] : $callback;

                foreach ($callback as $actualCallback) {
                    call_user_func_array([$this->handler, $actualCallback], [$this]);
                }
            }
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

    private function validatePayload()
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

    private function logPayload()
    {
        $this->log(
            'Callback Received for momo transaction to '.$this->getPayload('phone').
            "\nPayload: ".$this->jsonEncode($this->getPayload()),
            $this->callbackLogFile()
        );
    }

    public function getLogger()
    {
        return $this->logger ?? $this->logger = new Log();
    }

    public function log(string $message, $file = '', $level = 'info')
    {
        if (!$this->canLog || env('TXTPAY_LOG_ENABLED', null) === false) {
            return $this;
        }

        SlackLog::log($message, $level);

        if (env('TXTPAY_LOCAL_LOG_ENABLED', true) === false || !$file || !$this->getLogger()) {
            return $this;
        }

        $this->getLogger()
            ->setFile($file)
            ->{$level}($message);

        return $this;
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

    public function getTransactionId()
    {
        return $this->getId();
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
            return json_encode($value, JSON_PRETTY_PRINT | $options, $depth);
        }

        return json_encode($value, $options, $depth);
    }

    public function setJsonPrettyPrint(bool $pretty)
    {
        $this->jsonPrettyPrint = $pretty;

        return $this;
    }
}
