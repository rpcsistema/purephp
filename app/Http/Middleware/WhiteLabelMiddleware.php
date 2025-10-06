<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\WhiteLabelSetting;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;

class WhiteLabelMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for super admin routes
        if ($request->is('super-admin/*') || $request->is('login') || $request->is('register')) {
            return $next($request);
        }

        // Get tenant ID from authenticated user
        $tenantId = null;
        if (auth()->check()) {
            $tenantId = auth()->user()->tenant_id;
        }

        // Get white label settings
        $whiteLabelSettings = null;
        if ($tenantId) {
            $whiteLabelSettings = WhiteLabelSetting::getForTenant($tenantId);
        }

        // If no settings found, create default ones
        if (!$whiteLabelSettings && $tenantId) {
            $whiteLabelSettings = WhiteLabelSetting::create(
                array_merge(['tenant_id' => $tenantId], WhiteLabelSetting::getDefaultSettings())
            );
        }

        // Share settings with all views
        if ($whiteLabelSettings) {
            // Share with Blade views
            View::share('whiteLabelSettings', $whiteLabelSettings);
            
            // Share with Inertia
            Inertia::share([
                'whiteLabelSettings' => function () use ($whiteLabelSettings) {
                    return [
                        'company_name' => $whiteLabelSettings->company_name,
                        'app_name' => $whiteLabelSettings->app_name,
                        'tagline' => $whiteLabelSettings->tagline,
                        'logo_url' => $whiteLabelSettings->logo_url,
                        'favicon_url' => $whiteLabelSettings->favicon_url,
                        'sidebar_logo_url' => $whiteLabelSettings->sidebar_logo_url,
                        'login_background_url' => $whiteLabelSettings->login_background_url,
                        'footer_text' => $whiteLabelSettings->footer_text,
                        'dashboard_welcome_message' => $whiteLabelSettings->dashboard_welcome_message,
                        'colors' => $whiteLabelSettings->getColorPalette(),
                        'theme_config' => $whiteLabelSettings->getThemeConfiguration(),
                        'enabled_modules' => $whiteLabelSettings->getEnabledModules(),
                        'social_links' => $whiteLabelSettings->getSocialLinks(),
                        'contact_info' => $whiteLabelSettings->getContactInfo(),
                        'meta_title' => $whiteLabelSettings->meta_title,
                        'meta_description' => $whiteLabelSettings->meta_description,
                        'meta_keywords' => $whiteLabelSettings->meta_keywords,
                        'custom_css' => $whiteLabelSettings->generateCssVariables(),
                        'custom_js' => $whiteLabelSettings->custom_js,
                    ];
                },
            ]);

            // Add custom CSS to response if it's an HTML response
            $response = $next($request);
            
            if ($response instanceof \Illuminate\Http\Response && 
                str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
                
                $content = $response->getContent();
                
                // Inject custom CSS
                $customCss = $whiteLabelSettings->generateCssVariables();
                if ($customCss) {
                    $cssTag = "<style id=\"white-label-css\">\n{$customCss}\n</style>";
                    $content = str_replace('</head>', $cssTag . "\n</head>", $content);
                }
                
                // Inject custom JS
                if ($whiteLabelSettings->custom_js) {
                    $jsTag = "<script id=\"white-label-js\">\n{$whiteLabelSettings->custom_js}\n</script>";
                    $content = str_replace('</body>', $jsTag . "\n</body>", $content);
                }
                
                // Update meta tags
                if ($whiteLabelSettings->meta_title) {
                    $content = preg_replace(
                        '/<title>.*?<\/title>/',
                        '<title>' . htmlspecialchars($whiteLabelSettings->meta_title) . '</title>',
                        $content
                    );
                }
                
                if ($whiteLabelSettings->meta_description) {
                    $metaDescription = '<meta name="description" content="' . htmlspecialchars($whiteLabelSettings->meta_description) . '">';
                    if (strpos($content, 'name="description"') !== false) {
                        $content = preg_replace(
                            '/<meta name="description"[^>]*>/',
                            $metaDescription,
                            $content
                        );
                    } else {
                        $content = str_replace('</head>', $metaDescription . "\n</head>", $content);
                    }
                }
                
                if ($whiteLabelSettings->meta_keywords) {
                    $metaKeywords = '<meta name="keywords" content="' . htmlspecialchars($whiteLabelSettings->meta_keywords) . '">';
                    if (strpos($content, 'name="keywords"') !== false) {
                        $content = preg_replace(
                            '/<meta name="keywords"[^>]*>/',
                            $metaKeywords,
                            $content
                        );
                    } else {
                        $content = str_replace('</head>', $metaKeywords . "\n</head>", $content);
                    }
                }
                
                // Update favicon
                if ($whiteLabelSettings->favicon_url) {
                    $faviconTag = '<link rel="icon" type="image/x-icon" href="' . $whiteLabelSettings->favicon_url . '">';
                    if (strpos($content, 'rel="icon"') !== false) {
                        $content = preg_replace(
                            '/<link[^>]*rel="icon"[^>]*>/',
                            $faviconTag,
                            $content
                        );
                    } else {
                        $content = str_replace('</head>', $faviconTag . "\n</head>", $content);
                    }
                }
                
                $response->setContent($content);
            }
            
            return $response;
        }

        return $next($request);
    }
}