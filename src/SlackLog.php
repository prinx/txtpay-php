<?php

namespace Txtpay;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use function Prinx\Dotenv\env;

/**
 * Simple log to slack.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class SlackLog
{
    /**
     * HTTP Client
     *
     * @var \Symfony\Component\HttpClient\HttpClient
     */
    protected static $httpClient;

    public static function log($message, $level = 'info')
    {
        if (!($url =  env('SLACK_LOG_WEBHOOK', null))) {
            return;
        }

        try {
            static::logger()->request('POST', $url, [
                'json' => [
                    'text' => '['.strtoupper($level).'] ['.date('D, d m Y, H:i:s')."]\n".$message,
                ],
            ]);
        } catch (HttpExceptionInterface $th) {
            //
        } catch (TransportExceptionInterface $th) {
            //
        } catch (DecodingExceptionInterface $th) {
            //
        } catch (\Throwable $th) {
            //
        }
    }

    public static function logger()
    {
        return static::$httpClient ?: static::$httpClient = HttpClient::create();
    }
}
