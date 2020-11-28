<?php

namespace Txtpay\Contracts;

/**
 * MobileMoneyInterface.
 */
interface MobileMoneyInterface
{
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
    );

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
    );

    public function generateToken();

    public function getTransactionId();

    public function getTokenUrl();

    public function getPaymentUrl();

    public function log($data, $level = 'info');

    public function getLogFile();

    public function getDefaultLogFile();

    public function setLogFile($file);

    public function setLogger($logger);

    public function getLogger();

    public function getAccount();

    public function getApiId();

    public function getApiKey();

    public function setNetwork($network);

    public function getNetwork();

    public function setPrimaryCallback(string $callback);

    public function getPrimaryCallback();

    public function setSecondaryCallback(string $callback);

    public function getsecondaryCallback();

    public function setDescription(string $description);

    public function getDescription();

    public function setNickname(string $nickname);

    public function getNickname();

    public function setVoucherCode($voucherCode);

    public function getVoucherCode();

    public function setAmount($amount);

    public function getAmount();

    public function setPhone(string $phone);

    public function getPhone();
}
