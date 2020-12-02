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

use function Prinx\Dotenv\env;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

/**
 * Simple log to slack.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class SlackLog
{
    /**
     * HTTP Client.
     *
     * @var \Symfony\Component\HttpClient\HttpClient
     */
    protected static $httpClient;

    public static function log($message, $level = 'info')
    {
        if (!($url = env('SLACK_LOG_WEBHOOK', null))) {
            return;
        }

        try {
            static::getLogger()->request('POST', $url, [
                'json' => [
                    'text' => '['.strtoupper($level).'] ['.date('D, d m Y, H:i:s')."]\n".$message,
                ],
            ]);
        } catch (Throwable $th) {
            //
        }
    }

    public static function getLogger()
    {
        return static::$httpClient ?: static::$httpClient = HttpClient::create();
    }
}
