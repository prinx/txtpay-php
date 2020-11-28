<?php

namespace Txtpay\Laravel;

use Illuminate\Support\ServiceProvider;
use Txtpay\Callback;
use Txtpay\Contracts\CallbackInterface;
use Txtpay\Contracts\MobileMoneyInterface;
use Txtpay\MobileMoney;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(CallbackInterface::class, Callback::class);
        $this->app->bind(MobileMoneyInterface::class, function () {
            return (new MobileMoney)->autoConfig();
        });
    }

    public function provides()
    {
        return [MobileMoneyInterface::class, CallbackInterface::class];
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
