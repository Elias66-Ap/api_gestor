<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Tarea;
use App\Observers\TareaObserver;

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
        Tarea::observe(TareaObserver::class);
    }
}
