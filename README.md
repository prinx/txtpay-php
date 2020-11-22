# txtpay-php

TXTGHANA Payment Gateway PHP SDK

## Installation

```shell
composer require txtghana/txtpay
```

## Usage

Configure in the .env file at the root of the project:

```ini
TXTPAY_ID=
TXTPAY_KEY=
TXTPAY_ACCOUNT=
TXTPAY_NICKNAME=
TXTPAY_DESCRIPTION=
TXTPAY_PRIMARY_CALLBACK=
TXTPAY_SECONDARY_CALLBACK=
```

### Request a payment

```php
$payment = MobileMoney::create();

$amount = 1; // 1 GHC
$phone = '233...';
$network = 'MTN'; // AIRTEL|VODAFONE

$payment->request($amount, $phone, $network);
```

#### Voucher code

Some networks (VODAFONE) require the user to generate some extra code called voucher code. This code can be easily passed to the request either by setting it on the callback or passing it to the `request` method.

```php
$payment->setVoucherCode($voucherCode);
$payment->request($amount, $phone, $network);

// or

$payment->request($amount, $phone, $network, $voucherCode);
```

### Process callback

The result of the transaction will be sent to the callback URL provided when sending the request.
Capture and process the callback like below:

```php
// callback.php or controller of url/to/callback.php

use Txtpay\Callback

$callback = new Callback();

$callback->success(function ($payload) {
    // Transaction was successful. Do stuff.
})->failure(function ($payload) {
    // Transaction failed. Do stuff.
});
```

#### Successful transaction

The code of the transaction determined if the transaction is successful or has failed. The default, the successful transaction codes are in the array returned by `$callback->getSuccessCodes()`. You can decide (for any reason it may be) to consider a failure code as success code by adding it to the success codes with the `$callback->addSuccessCodes($code)` method.

#### The `on` method

You can also use the `on` method. It takes the a first parameter that matches the request payload amd the callback closure that must be run if the first parameter matches the payload.

```php
// callback.php or controller of url/to/callback.php

use Txtpay\Callback

$callback = new Callback();

// If a string is passed, it is supposed to be the code sent in the payload to the callback URL.
$callback->on('000', function ($payload) {
    //
})->on('101', function ($payload) {
    //
})->on(['code' => '000', 'subscriber_number' => '233...'], function ($payload) {
    //
})->success(function ($payload){
    //
})->failure(function () {
    //
});
```

The Callback class implements the [fluent interface](https://wikipedia.org/wiki/Fluent_interface). You can chain the `on`, `success`, `failure` methods (in any order).

### Changing default required parameter

By default, if a string is passed as first argument of the `on` method, it is considered as the code in the payload. You can change this behavior by using the `$callback->setDefaultRequiredParameter()` method.

```php
// Eg:
$callback->setDefaultRequiredParameter('phone');
```

### The payload

A payload is sent to the callback URL. It contains 8 parameters:

#### code

The code of the transaction.

```php
$callback->getPayload('code');
```

#### status

```php
$callback->getPayload('status'); // approved, declined...
```

#### details

The detail message of the status.

```php
$callback->getPayload('details');
```

#### id

The transaction ID.

```php
$callback->getPayload('id');
```

#### network

```php
$callback->getPayload('network'); // MTN, AIRTEL, VODAFONE, ...
```

#### phone

The phone number.

```php
$callback->getPayload('phone'); // 233...
```

#### amount

```php
$callback->getPayload('amount');
```

#### currency

```php
$callback->getPayload('currency'); // GHS
```

You can get all the payload array by calling the `getPayload` method without parameter.

```php
$payload = $callback->getPayload();
```

### Using the mobile money callback instance in the closure

The callback instance is automatically passed to the closure. You can then use it as below:

```php
$callback->success(function ($payload, $callback)
{
    $code = $payload['code'];
    $transactionId = $payload['id'];

    $message = $callback->messages($code, $transactionId);
});
```

### Passing other parameter to the closure

You can easily pass other parameters to the closure by using the PHP `use` keyword:

```php

$sms = new SmsService();

$callback->success(function ($payload, $callback) use ($sms)
{
    $code = $payload['code'];
    $transactionId = $payload['id'];
    $message = $callback->messages($code, $transactionId);

    $phone = $payload['phone'];

    $sms->send($message, $phone);
});
```

### Logging

#### Logging locally

You can provide a log folder where the transactions will be automatically logged.

```php
$callback->setLogFolder($path);
```

#### Logging to SLACK

You can provide in your .env file a slack webhook to automatically log transactions to slack.

```ini
SLACK_LOG_WEBHOOK=https://
```
