<?php

namespace Txtpay\Contracts;

use Closure;

/**
 * CallbackInterface.
 */
interface CallbackInterface
{
    public function __construct();

    /**
     * Register the callback if conditions match the request parameters.
     *
     * @param string|array $conditions String or associative array matching the request parameters.
     *                             If string, the parameter is the defaultConditionName.
     * @param Closure $callback
     *
     * @return $this
     */
    public function on($conditions, Closure $callback);

    /**
     * Register callback if the transaction is successful.
     *
     * The successful transaction is determined by the code of the request.
     *
     * @param Closure $callback
     *
     * @return $this
     */
    public function success(Closure $callback);

    /**
     * Run callback if the transaction has failed.
     *
     * The failed request is determined by the code of the request.
     *
     * @param Closure $callback
     *
     * @return $this
     */
    public function failure(Closure $callback);

    /**
     * Run callback whether the transaction is successful or not.
     *
     * @param Closure $callback
     *
     * @return $this
     */
    public function always(Closure $callback);

    /**
     * Register callback.
     *
     * @param Closure $callback
     *
     * @return $this
     */
    public function register(Closure $callback);

    /**
     * Run the registered callbacks against the callback request.
     *
     * @param Closure $callback Optional callback that will be run after all the callbacks have been run.
     *
     * @return $this
     */
    public function process(Closure $callback = null);

    public function runCallbacks();

    /**
     * Success codes.
     *
     * @return array
     */
    public function getSuccessCodes();

    /**
     * Failure codes.
     *
     * @return array
     */
    public function getFailureCodes();

    public function captureRequest();


    public function setPayload($payload = []);

    public function getPayload($attribute = null);

    public function validatePayload($payload);

    public function logPayload();


    public function getLogger();

    public function log($message, $file = '', $level = 'info');


    public function isSuccessful();

    public function failed();


    public function getMessages($code = null, $transactionId = null);

    public function getMessage();

    public function respond($message);


    public function callbackLogFile();

    public function successLogFile();

    public function failureLogFile();

    public function logFolder($append = '');

    public function setLogFolder($folder);


    public function getId();

    public function getPhone();

    public function getAmount();

    public function getCode();

    public function getStatus();

    public function getDetails();

    public function getNetwork();

    public function getCurrency();
}
