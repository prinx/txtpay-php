<?php

/*
 * This file is part of the Txtpay package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Txtpay\Abstracts;

use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\BoolType;
use Respect\Validation\Rules\Instance;
use Respect\Validation\Rules\Nullable;
use Respect\Validation\Rules\Optional;
use Respect\Validation\Rules\StringType;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Txtpay\Contracts\MobileMoneyResponseInterface;

abstract class MobileMoneyResponse implements MobileMoneyResponseInterface
{
    protected $responseBag = [];

    /**
     * Validation rules.
     *
     * @return \Respect\Validation\Validatable[]
     */
    public static function validationRules()
    {
        return [
            'isBeingProcessed' => new BoolType(),
            'body'             => new ArrayType(),
            'bodyRaw'          => new StringType(),
            'full'             => new Instance(ResponseInterface::class),
            'error'            => new Optional(new Nullable(new StringType())),
        ];
    }

    public function __construct(array $responseBag)
    {
        $responseBag['error'] = $responseBag['error'] ?? null;
        $this->validateResponse($responseBag);
        $this->responseBag = $responseBag;
    }

    /**
     * Validate response bag.
     *
     * @throws \Respect\Validation\Exceptions\ValidationException
     *
     * @return void
     */
    public function validateResponse(array $responseBag)
    {
        foreach (self::validationRules() as $key => $rule) {
            $rule->check($responseBag[$key] ?? null);
        }
    }
}
