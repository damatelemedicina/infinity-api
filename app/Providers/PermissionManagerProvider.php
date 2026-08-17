<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\Security\PermissionManager;
use App\Services\Security\PermissionManagerBase;

class PermissionManagerProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(PermissionManager::class, PermissionManagerBase::class);
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
