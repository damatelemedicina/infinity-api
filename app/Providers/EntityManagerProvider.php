<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Log;

use App\Services\Entity\EntityManager;
use App\Services\Entity\EntityManagerBase;

class EntityManagerProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(EntityManager::class, EntityManagerBase::class);
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

// https://code.tutsplus.com/tutorials/how-to-register-use-laravel-service-providers--cms-28966