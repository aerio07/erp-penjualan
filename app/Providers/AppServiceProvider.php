<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');

        $this->app->extend('view.finder', function ($finder, $app) {
            return new \App\View\PrefixAwareViewFinder(
                $app['files'],
                $finder->getPaths(),
                $finder->getExtensions()
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('rupiah', function ($expression) {
            return "<?php echo rupiah($expression); ?>";
        });

        Blade::directive('date', function ($expression) {
            return "<?php echo format_date($expression); ?>";
        });

        Blade::directive('datetime', function ($expression) {
            return "<?php echo format_datetime($expression); ?>";
        });

        \Carbon\Carbon::macro('toAppDate', function () {
            return $this->format('d/m/Y');
        });

        \Carbon\Carbon::macro('toAppDateTime', function () {
            return $this->format('d/m/Y H:i');
        });

        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.custom');
        \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.custom');
    }
}
