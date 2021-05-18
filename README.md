# TXTPAY PHP SDK

<p>
<a href="https://travis-ci.org/prinx/txtpay-php"><img src="https://travis-ci.com/prinx/txtpay-php.svg?branch=main" alt="Build Status"></a>
<a href="https://packagist.org/packages/prinx/txtpay-php"><img src="https://poser.pugx.org/prinx/txtpay/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/prinx/txtpay-php"><img src="https://poser.pugx.org/prinx/txtpay/license.svg" alt="License"></a>
</p>

TXTGHANA Payment Gateway PHP SDK

## 🔥 DEPRECATION

If this is your first time to use the package, we recommend to use its successor that you can find [here](https://github.com/prinx/sevotransact).

### Why?

Following the change of name of TxtPay to Sevotransact, things changed and we decided to rather create a new package following the same spirit as this one.

### But I'm already using this!

If you are already using this package and cannot switch to the new, don't worry. We will still be maintaining this package (even though we will be happy you switch).

## Installation

The package can be installed via composer. Install it if you don't have it yet. Then open a terminal in your project root folder and run:

```shell
composer require prinx/txtpay
```

## Usage

First create a `.env` file in your project root folder, if you don't have any already.

Next, configure the .env by putting your txtpay credentials and account informations:

```ini
#...

TXTPAY_ID=your_txtpay_id
TXTPAY_KEY=your_txtpay_key
TXTPAY_ACCOUNT=your_txtpay_account
TXTPAY_NICKNAME=your_txtpay_nickname
TXTPAY_DESCRIPTION=your_txtpay_description
TXTPAY_PRIMARY_CALLBACK=primary_callback
TXTPAY_SECONDARY_CALLBACK=secondary_callback
```

The primary and secondary callbacks are URL where `TXTPAY` will send the result of the transaction. YOu can check how to handle the transaction callback [here](#process-callback). The secondary callback is optional.

### Request a payment

```php
require 'path/to/vendor/autoload.php'; // Not needed if using a framework.

use Txtpay\MobileMoney;

$payment = new MobileMoney;

$amount = 1; // 1 GHC
$phone = '233...';
$network = 'MTN'; // MTN|AIRTEL|VODAFONE

$request = $payment->request($amount, $phone, $network);
```

#### Voucher code

Some networks (typically VODAFONE) require the user to generate a voucher code. This code can be easily passed to the request:

```php
$request = $payment->request($amount, $phone, $network, $voucherCode);

// or

$payment->setVoucherCode($voucherCode);
$request = $payment->request($amount, $phone, $network);
```

> The voucher code is now optional. If not passed, the user will directly receive a prompt on their phone to accept the transaction.

#### Mobile money request response

The mobile money request will automatically return a response from which you can determine if the request is being processed or not.

```php
$request = $payment->request($amount, $phone, $network);

if ($request->isBeingProcessed()) {
    $status = $request->getStatus();

    // ...
} else {
    $error = $request->getError();

    // ...
}
```

> WARNING:
This response is just to notify you that your request has been received and is been processed or something went wrong when sending the request. This is not the actual response of the mobile money request. The actual response of the mobile money request will be sent to your provided callback URL.

### Process callback

The result of the transaction will be sent to the callback URL provided when sending the request.
To process the callback, create a new route in your favorite framework, or create an php file in your project to handle the callback. The URL resolving to that route or that file is (must be) the one you provided in the `.env.` as callback URL.

Now you can create a callback instance that will receive the status of your transactions:

```php
// File: callback.php

require 'path/to/vendor/autoload.php';

use Txtpay\Callback;

$callback = new Callback;
```

Or if you are using a framework and the callback route is handled by a controller:

```php
// This is just an example.
// Write your controller the way you are used to with your favorite framework.

namespace Controllers;

use Txtpay\Callback;

class MobileMoneyCallbackController extends Controller
{
    public function processCallback()
    {
        $callback = new Callback;
    }
}
```

We can now register the callback functions that will be run on success, failure or some defined custom condition of the mobile money transaciton. The callback functions will receive the `$callback` instance.

```php
$callback->success(function (Callback $callback) {
    // Transaction was successful. Do stuff.
})->failure(function (Callback $callback) {
    // Transaction failed. Do stuff.
})->always(function (Callback $callback) {
    // Do stuff whether transaction was successful or not.
});
```

> You are not require to use all the callbacks. Just use the one that is needed.

Now, run everything, by calling the `process` method on the callback.

```php
$callback->process();
```

The full code will be:

```php
$callback = new Callback;

$callback->success(function (Callback $callback) {
    //
})->failure(function (Callback $callback) {
    //
})->always(function (Callback $callback) {
    //
})->process();
```

>TIP:
In case you have only one callback function that will be run whether the transaction is successful or not, you can simply pass the function to the process method:

```php
$callback = new Callback;

$callback->process(function (Callback $callback) {
    //
});
```

#### Successful transaction

The code of the transaction determines if the transaction is successful or has failed. By default, the successful transaction codes are in the array returned by `$callback->getSuccessCodes()`. You can decide (for any reason it may be) to consider a failure code as success code by adding it to the success codes with the `$callback->addSuccessCode($code)` method.

#### The `on` method

The `on` method is a powerful way of listening to the transaction request callbacks. It takes as first parameter an array of conditions that can match the request payload and as second parameter a callback function that will be run if the conditions match the payload.

A string can be passed as condition, then it will be considered as the code sent in the payload to the callback URL.

```php
use Txtpay\Callback;

$callback = new Callback;

$callback->on('000', function (Callback $callback) {
    //
})->on('101', function (Callback $callback) {
    //
})->on(['code' => '000', 'phone' => '233...'], function (Callback $callback) {
    //
})->success(function (Callback $callback){
    // We can still chain the success or failure methods.
})->failure(function (Callback $callback) {
    //
})->process();
```

The Callback class implements the [fluent interface](https://wikipedia.org/wiki/Fluent_interface). You can chain most of its methods (like the `on`, `success`, `failure` methods), in any order.

The callbacks will be run in the order they were registered.

### The payload

A payload is sent to the callback URL. It contains 8 parameters:

#### code

The code of the request.

```php
$requestCode = $callback->getCode();
```

#### status

```php
$requestStatus = $callback->getStatus(); // approved, declined...
```

#### details

The detail message of the status.

```php
$requestDetails = $callback->getDetails();
```

#### id

The transaction ID.

```php
$transactionId = $callback->getId();
```

#### phone

The phone number to which the request was made.

```php
$phone = $callback->getPhone(); // 233...
```

#### network

The network to which belong the phone number.

```php
$network = $callback->getNetwork(); // MTN, AIRTEL, VODAFONE, ...
```

#### amount

The amount of the transaction.

```php
$amount = $callback->getAmount();
```

#### currency

The currency in which the transaction was made.

```php
$currency = $callback->getCurrency(); // GHS
```

You can get all the payload array by calling the `getPayload` method without parameter.

```php
$payload = $callback->getPayload();
```

You can also get any of the payload parameters by passing the name of the parameter to the `getPayload` method, for example:

```php
$transactionId = $callback->getPayload('id');
$transactionCode = $callback->getPayload('code');
```

### Message associated to the request

You can get the message associated to the request by calling the `message` method of the callback instance.

```php
$message = $callback->getMessage();
```

The message is associated to the code in the payload.

### Messages

These are the possible messages:

```php
[
    '000'     => 'Transaction successful. Your transaction ID is '.$transactionId,
    '101'     => 'Transaction failed. Insufficient fund in wallet.',
    '102'     => 'Transaction failed. Number non-registered for mobile money.',
    '103'     => 'Transaction failed. Wrong PIN. Transaction timed out.',
    '104'     => 'Transaction failed. Transaction declined',
    '114'     => 'Transaction failed. Invalid voucher',
    '909'     => 'Transaction failed. Duplicate transaction id.',
    'default' => 'Transaction failed.',
];
```

### Using the mobile money callback instance in the closure

The callback instance is automatically passed to the closure. You can then use it as below:

```php
$callback->success(function (Callback $callback) {
    $message = $callback->getMessage();
});
```

### Passing other parameter(s) to the closure

You can easily pass other parameters to the closure by using the PHP `use` keyword on the closure:

```php

// SmsService is your own implementation.
$sms = new SmsService();

$callback->success(function (Callback $callback) use ($sms) {
    $message = $callback->getMessage();
    $phone = $callback->getPhone();

    $sms->send($message, $phone);
});
```

### Using a class to handle callbacks

Instead of directly passing the callbacks closures to the `success`, `failure` or `on` methods, you can move all the callbacks to a class and just pass the class to the `process` method.

First, create a class containing a `callbacks` methods:

```php
class CallbackHandler
{
    public function callbacks(Callback $callback)
    {
        //
    }
}
```

The `callbacks` method must return an array containing the mapping of your conditions with the callback closures:

```php
class CallbackHandler
{
    public function callbacks(Callback $callback)
    {
        return [
            ['000', function (Callback $callback) {
                $message = $callback->getMessage();
            }],
            ['101', function (Callback $callback) {
                //
            }],
        ];
    }
}
```

The mappings can be duplicated:

```php
class CallbackHandler
{
    public function callbacks(Callback $callback)
    {
        return [
            ['000', function (Callback $callback) {
                //
            }],
            ['000', function (Callback $callback) {
                // Another callback that will be run for the same code.
            }],
            ['101', function (Callback $callback) {
                //
            }],
        ];
    }
}
```

Or you can rather passed an array of closures:

```php
class CallbackHandler
{
    public function callbacks(Callback $callback)
    {
        return [
            ['000', [
                function (Callback $callback) {
                    //
                },
                function (Callback $callback) {
                    // Another callback that will be run for the same code.
                },
            ]],
            ['101', function (Callback $callback) {
                //
            }],
        ];
    }
}
```

You can pass the custom conditions `success`, `failure` or `always`:

```php
class CallbackHandler
{
    public function callbacks(Callback $callback)
    {
        return [
            ['success', function (Callback $callback) {
                //
            }],
            ['failure', function (Callback $callback) {
                //
            }],
            ['always', function (Callback $callback) {
                //
            }],
        ];
    }
}
```

You can also pass an array of conditions:

```php
class CallbackHandler
{
    public function callbacks(Callback $callback)
    {
        return [
            [['code' => '000', 'phone' => '233...'], function (Callback $callback) {
                    //
            }],
        ];
    }
}
```

All these can be mixed according to your need:

```php
class CallbackHandler
{
    public function callbacks(Callback $callback)
    {
        return [
            ['000', function (Callback $callback) {
                //
            }],
            [['code' => '000', 'phone' => '233...'], function (Callback $callback) {
                //
            }],
            ['success', function (Callback $callback) {
                //
            }],
            ['always', function (Callback $callback) {
                //
            }],
        ];
    }
}
```

Instead of returning directly closures in the callbacks methods, you can create public methods in the callback handler class and reference those methods in the array returned in the `callbacks` method:

```php
class CallbackHandler
{
    public function callbacks(Callback $callback)
    {
        return [
            ['000', 'sendSms'],
            ['101', ['notifyAdmin', 'sendSms']]
            ['always', 'log'],
        ];
    }

    public function notifyAdmin(Callback $callback)
    {
        // Notify admin...
    }

    public function sendSms(Callback $callback)
    {
        //
    }

    public function log(Callback $callback)
    {
        //
    }
}
```

After writing the callback handler class, you just need to pass it to the process method:

```php
// Do not forget to import the class from it namespace
use App\Helpers\CallbackHandler;

$callback = new Callback;

$callback->process(CallbackHandler::class);
```

### Logging

#### Logging locally

You can provide a log folder where the transactions will be automatically logged.

```php
$callback->setLogFolder($path);
```

If no folder has been set, a default log folder will be created at `YOUR_PROJECT_ROOT_FOLDER/storage/logs/txtpay/`.

You can disable local logs by using the env variable (It is enabled by default):

```ini
TXTPAY_LOCAL_LOG_ENABLED=false
```

#### Logging to SLACK

You can provide in your `.env` file a slack webhook to automatically log transactions to slack.

```ini
TXTPAY_SLACK_LOG_WEBHOOK=https://
```

You can disable logging to SLACK by putting in the `.env`:

```ini
TXTPAY_SLACK_LOG_ENABLED=false
```

It is enabled by default.

#### Disable logs

To disable the whole logging system, put in your `.env` file:

```ini
TXTPAY_LOG_ENABLED=false
```

## Contribute

Star :star: the repo, fork to add new features to the package and submit pull requests.

## License

[MIT](LICENSE)
