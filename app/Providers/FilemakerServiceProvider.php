<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use INTERMediator\FileMakerServer\RESTAPI\FMDataAPI;

class FilemakerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FMDataAPI::class, function () {
            $api = new FMDataAPI(
                config('filemaker.database'),
                config('filemaker.username'),
                config('filemaker.password'),
                config('filemaker.hostname'),
                config('filemaker.port'),
                config('filemaker.protocol')
            );

            $api->setDebug(config('filemaker.debug'));
            $api->setThrowException(config('app.debug'));

            return $api;
        });

        $this->app->alias(FMDataAPI::class, 'filemaker');
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
