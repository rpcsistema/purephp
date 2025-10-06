<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Models\WhiteLabelSetting;

class WhiteLabelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Blade directives for white label
        Blade::directive('whitelabel', function ($expression) {
            return "<?php echo app('whitelabel')->get({$expression}); ?>";
        });

        Blade::directive('companyName', function () {
            return "<?php echo app('whitelabel')->get('company_name', config('app.name')); ?>";
        });

        Blade::directive('appName', function () {
            return "<?php echo app('whitelabel')->get('app_name', config('app.name')); ?>";
        });

        Blade::directive('logoUrl', function () {
            return "<?php echo app('whitelabel')->get('logo_url', '/images/logo.png'); ?>";
        });

        // Register singleton for white label settings
        $this->app->singleton('whitelabel', function ($app) {
            $tenantId = null;
            if (auth()->check()) {
                $tenantId = auth()->user()->tenant_id;
            }

            if ($tenantId) {
                return WhiteLabelSetting::getForTenant($tenantId);
            }

            return new class {
                public function get($key, $default = null) {
                    return $default;
                }
            };
        });
    }
}