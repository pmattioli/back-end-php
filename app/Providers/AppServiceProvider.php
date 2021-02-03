<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RetinaLyze\Chain\ChainEditor;
use RetinaLyze\Users\UserEditor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(UserEditor::class, function ($app) {
            return new UserEditor();
        });
        $this->app->singleton(ChainEditor::class, function ($app) {
            return new ChainEditor();
        });
    }
}
