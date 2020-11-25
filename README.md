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
$network = 'MTN'; // MTN|AIRTEL|VODAFONE

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
To process the callback, you first need to create a callback instance:

```php
// callback.php
use Txtpay\Callback;

$callback = new Callback();
```

If the callback route is handled by a controller:

```php
// This is just an example.
// Write your controller the way you are used to with your favorite framework.

namespace Controllers;

use Txtpay\Callback;

class MobileMoneyCallbackController extends Controller
{
    public function processCallback()
    {
        $callback = new Callback();
    }
}
```

Everything is the same except the code will be in a method instead of directly in the file.

Now, we must register the callback functions that will be run on success, failure or some defined custom condition of the mobile money transaciton. The callback will receive the `$payload` and the `$callback` instance.

```php
$callback->success(function ($payload, $callback) {
    // Transaction was successful. Do stuff.
})->failure(function ($payload, $callback) {
    // Transaction failed. Do stuff.
});
```

Now, run Everything, by calling the `process` method on the callback.

```php
$callback->process();
```

The full code will be:

```php
$callback = new Callback();

$callback->success(function ($payload, $callback) {
    //
})->failure(function ($payload, $callback) {
    //
})->process();
```

>TIP
In case you have only one callback function that will be run whether the transaction was successful or not, you can simply pass the function to the process method:

```php
$callback = new Callback();

$callback->process(function ($payload, $callback) {
    //
});
```

#### Successful transaction

The code of the transaction determines if the transaction is successful or has failed. By default, the successful transaction codes are in the array returned by `$callback->getSuccessCodes()`. You can decide (for any reason it may be) to consider a failure code as success code by adding it to the success codes with the `$callback->addSuccessCode($code)` method.

#### The `on` method

The `on` method takes as first parameter an array of conditions that can match the request payload and as second parameter a callback function that will be run if the conditions match the payload.

A string can be passed as condition, then it will be considered as the code sent in the payload to the callback URL.

```php
use Txtpay\Callback;

$callback = new Callback();

$callback->on('000', function ($payload) {
    //
})->on('101', function ($payload) {
    //
})->on(['code' => '000', 'phone' => '233...'], function ($payload) {
    //
})->success(function ($payload){
    // We can still chain the success or failure methods.
})->failure(function () {
    //
})->process();
```

The Callback class implements the [fluent interface](https://wikipedia.org/wiki/Fluent_interface). You can chain the `on`, `success`, `failure` methods (in any order).

The callback will be run in the order they were registered.

### The payload

A payload is sent to the callback URL. It contains 8 parameters:

#### code

The code of the transaction.

```php
$callback->code();
```

#### status

```php
$callback->status(); // approved, declined...
```

#### details

The detail message of the status.

```php
$callback->details();
```

#### id

The transaction ID.

```php
$callback->id();
```

#### network

```php
$callback->network(); // MTN, AIRTEL, VODAFONE, ...
```

#### phone

The phone number.

```php
$callback->phone(); // 233...
```

#### amount

```php
$callback->amount();
```

#### currency

```php
$callback->currency(); // GHS
```

You can get all the payload array by calling the `getPayload` method without parameter.

```php
$payload = $callback->getPayload();
```

You can also get any of the payload parameters by passing the name of the parameter to the `getPayload` method, for example:

```php
$transactionId = $callback->getPayload('id');
$transactionCode = $callback->getPayload('code');
//...
```

### Message associated to the request

You can get the message associated to the request by calling the `message` method of the callback instance.

```php
$payload = $callback->message();
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
$callback->success(function ($payload, $callback) {
    $message = $callback->message();
});
```

### Passing other parameter(s) to the closure

You can easily pass other parameters to the closure by using the PHP `use` keyword on the closure:

```php

$sms = new SmsService();

$callback->success(function ($payload, $callback) use ($sms) {
    $message = $callback->message();
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

If no folder has been set, a default log folder will be created at `YOUR_PROJECT_ROOT_FOLDER/storage/logs/txtpay/`.

#### Logging to SLACK

You can provide in your .env file a slack webhook to automatically log transactions to slack.

```ini
SLACK_LOG_WEBHOOK=https://
```
