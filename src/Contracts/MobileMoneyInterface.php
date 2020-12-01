<?php

namespace Txtpay\Contracts;

/**
 * MobileMoneyInterface.
 */
interface MobileMoneyInterface
{
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
}
