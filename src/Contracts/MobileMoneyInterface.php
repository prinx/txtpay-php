<?php

/*
 * This file is part of the Txtpay package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Txtpay\Contracts;

/**
 * MobileMoneyInterface.
 */
interface MobileMoneyInterface
{
    /**
     * Send Mobile Money request to the specified phone number with the specified amount.
     *
     * @param string|int|float $amount
     * @param string           $phone
     * @param string           $network
     * @param string           $voucherCode
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
