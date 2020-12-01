<?php

require './vendor/autoload.php';

use Txtpay\Support\Combination;

$array = [
    'a' ,
    'b' ,
];
print_r(Combination::combine($array));