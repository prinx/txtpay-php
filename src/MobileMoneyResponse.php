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

use Symfony\Contracts\HttpClient\ResponseInterface;

class MobileMoneyResponse extends Abstracts\MobileMoneyResponse
{
    public function isBeingProcessed(): bool
    {
        return $this->responseBag('isBeingProcessed');
    }

    public function getError()
    {
        return $this->responseBag('error');
    }

    public function getBody(): array
    {
        return $this->responseBag('body');
    }

    public function getBodyRaw(): string
    {
        return $this->responseBag('bodyRaw');
    }

    public function getFull(): ResponseInterface
    {
        return $this->responseBag('full');
    }

    public function getStatus()
    {
        return $this->responseBag('status');
    }

    public function getTransactionId(): string
    {
        return $this->responseBag('transactionId');
    }

    public function responseBag($key = '')
    {
        if ($key && !array_key_exists($key, $this->responseBag)) {
            throw new \InvalidArgumentException('Key "'.$key.'" not defined in Mobile Money response.');
        }

        return $key ? $this->responseBag[$key] : $this->responseBag;
    }
}
