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

use Symfony\Contracts\HttpClient\ResponseInterface;

interface MobileMoneyResponseInterface
{
    public function isBeingProcessed(): bool;

    public function getError();

    public function getBody(): array;

    public function getBodyRaw(): string;

    public function getFull(): ResponseInterface;

    public function getStatus();

    public function getTransactionId(): string;
}
