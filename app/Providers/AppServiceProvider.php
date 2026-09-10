<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        $disk->buildTemporaryUrlsUsing(function ($uuid, $expiration, $options) {
            return URL::temporarySignedRoute(
                'uploads.temp',
                $expiration,
                array_merge($options, ['uuid' => \urlencode($uuid)]),
                false
            );
        });
    }
}
