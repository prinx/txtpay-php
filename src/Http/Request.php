<?php

/*
 * This file is part of the Txtpay package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Txtpay\Http;

use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class Request extends HttpRequest
{
    /**
     * Request body parameters ($_POST).
     *
     * @var InputBag|ParameterBag
     */
    public $input;

    /**
     * Capture Request.
     *
     * @return Request
     */
    public static function capture()
    {
        $request = self::createFromGlobals();

        if ($request->isJson() && $request->getContent()) {
            $data = (array) \json_decode($request->getContent(), true);
            $request->request->replace($data);
        }

        $request->input = $request->request;

        return $request;
    }

    public function isJson()
    {
        return $this->getContentType() === 'json';
    }
}
