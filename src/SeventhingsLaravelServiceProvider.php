<?php
namespace Hwkdo\SeventhingsLaravel;
use Illuminate\Support\ServiceProvider;

class SeventhingsLaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'seventhings-laravel-migrations');

        $this->publishes([
            __DIR__.'/config/seventhings-laravel.php' => config_path('seventhings-laravel.php'),
        ]);
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SeventhingsLaravel::class, function () {
            return new SeventhingsLaravel();
        });
        $this->app->alias(SeventhingsLaravel::class, 'seventhings-laravel');
        $this->mergeConfigFrom(
            __DIR__.'/config/seventhings-laravel.php', 'seventhings-laravel'
        );
    }
}