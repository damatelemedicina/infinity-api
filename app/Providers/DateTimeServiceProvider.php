<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\DateTime\DateTimeService;
use App\Services\DateTime\DateTimeServiceBase;

class DateTimeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(DateTimeService::class, DateTimeServiceBase::class);
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
