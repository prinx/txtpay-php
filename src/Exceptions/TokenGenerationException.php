<?php

/*
 * This file is part of the Txtpay package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Txtpay\Exceptions;

class TokenGenerationException extends \Exception
{
    public function __construct($message = '', $code = 0)
    {
        $message = $message ?: 'Cannot generate token. A possible reason is invalid API credentials.';
        parent::__construct($message, $code);
    }
}
