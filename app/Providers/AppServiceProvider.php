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
            return $this->format('m/d/Y');
        });

        \Carbon\Carbon::macro('toAppDateTime', function () {
            return $this->format('m/d/Y H:i');
        });
    }
}
