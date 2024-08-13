<?php

namespace CodingPartners\AutoController\Providers;

use Illuminate\Support\ServiceProvider;

class AutoControllerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \CodingPartners\AutoController\Console\Commands\AutoControllerCommand::class,
            ]);
        }
    }

    public function register()
    {
        // تسجيل الخدمات
    }
}
