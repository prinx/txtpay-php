<?php

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

    public function getStatus(): ResponseInterface
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
