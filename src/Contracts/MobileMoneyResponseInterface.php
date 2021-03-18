<?php

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
