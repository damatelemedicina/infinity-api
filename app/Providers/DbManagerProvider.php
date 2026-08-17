<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\Db\DbManager;
use App\Services\Db\DbManagerBase;

class DbManagerProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(DbManager::class, DbManagerBase::class);
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
