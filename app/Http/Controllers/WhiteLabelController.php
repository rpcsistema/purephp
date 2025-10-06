<?php

namespace App\Http\Controllers;

use App\Models\WhiteLabelSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class WhiteLabelController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        
        $settings = WhiteLabelSetting::where('tenant_id', $tenantId)->first();
        
        if (!$settings) {
            $settings = WhiteLabelSetting::create(
                array_merge(['tenant_id' => $tenantId], WhiteLabelSetting::getDefaultSettings())
            );
        }

        return Inertia::render('WhiteLabel/Index', [
            'settings' => $settings,
            'colorPresets' => $this->getColorPresets(),
            'themeOptions' => $this->getThemeOptions(),
            'moduleOptions' => $this->getModuleOptions(),
        ]);
    }

    public function update(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable|string|max:255',
            'app_name' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:500',
            'footer_text' => 'nullable|string|max:1000',
            'dashboard_welcome_message' => 'nullable|string|max:1000',
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'text_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
            'email_template_header' => 'nullable|string',
            'email_template_footer' => 'nullable|string',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url',
            'social_links.twitter' => 'nullable|url',
            'social_links.instagram' => 'nullable|url',
            'social_links.linkedin' => 'nullable|url',
            'social_links.youtube' => 'nullable|url',
            'contact_info' => 'nullable|array',
            'contact_info.email' => 'nullable|email',
            'contact_info.phone' => 'nullable|string|max:20',
            'contact_info.address' => 'nullable|string|max:500',
            'contact_info.website' => 'nullable|url',
            'features_enabled' => 'nullable|array',
            'modules_config' => 'nullable|array',
            'theme_config' => 'nullable|array',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png|max:512',
            'sidebar_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'login_background' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settings = WhiteLabelSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            WhiteLabelSetting::getDefaultSettings()
        );

        $data = $request->except(['logo', 'favicon', 'sidebar_logo', 'login_background']);

        // Handle file uploads
        if ($request->hasFile('logo')) {
            if ($settings->logo_url && !str_starts_with($settings->logo_url, 'http')) {
                Storage::disk('public')->delete($settings->logo_url);
            }
            $data['logo_url'] = $request->file('logo')->store('white-label/logos', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($settings->favicon_url && !str_starts_with($settings->favicon_url, 'http')) {
                Storage::disk('public')->delete($settings->favicon_url);
            }
            $data['favicon_url'] = $request->file('favicon')->store('white-label/favicons', 'public');
        }

        if ($request->hasFile('sidebar_logo')) {
            if ($settings->sidebar_logo_url && !str_starts_with($settings->sidebar_logo_url, 'http')) {
                Storage::disk('public')->delete($settings->sidebar_logo_url);
            }
            $data['sidebar_logo_url'] = $request->file('sidebar_logo')->store('white-label/sidebar-logos', 'public');
        }

        if ($request->hasFile('login_background')) {
            if ($settings->login_background_url && !str_starts_with($settings->login_background_url, 'http')) {
                Storage::disk('public')->delete($settings->login_background_url);
            }
            $data['login_background_url'] = $request->file('login_background')->store('white-label/backgrounds', 'public');
        }

        $settings->update($data);

        return back()->with('success', 'Configurações de white label atualizadas com sucesso!');
    }

    public function preview(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $settings = WhiteLabelSetting::where('tenant_id', $tenantId)->first();
        
        if (!$settings) {
            $settings = WhiteLabelSetting::getDefaultSettings();
        }

        // Merge with preview data
        $previewData = $request->all();
        $previewSettings = array_merge($settings->toArray(), $previewData);

        return response()->json([
            'css' => $this->generatePreviewCss($previewSettings),
            'settings' => $previewSettings,
        ]);
    }

    public function reset()
    {
        $tenantId = auth()->user()->tenant_id;
        $settings = WhiteLabelSetting::where('tenant_id', $tenantId)->first();

        if ($settings) {
            // Delete uploaded files
            $filesToDelete = [
                $settings->getRawOriginal('logo_url'),
                $settings->getRawOriginal('favicon_url'),
                $settings->getRawOriginal('sidebar_logo_url'),
                $settings->getRawOriginal('login_background_url'),
            ];

            foreach ($filesToDelete as $file) {
                if ($file && !str_starts_with($file, 'http')) {
                    Storage::disk('public')->delete($file);
                }
            }

            $settings->update(WhiteLabelSetting::getDefaultSettings());
        }

        return back()->with('success', 'Configurações resetadas para os valores padrão!');
    }

    public function export()
    {
        $tenantId = auth()->user()->tenant_id;
        $settings = WhiteLabelSetting::where('tenant_id', $tenantId)->first();

        if (!$settings) {
            return back()->with('error', 'Nenhuma configuração encontrada para exportar.');
        }

        $exportData = $settings->toArray();
        unset($exportData['id'], $exportData['tenant_id'], $exportData['created_at'], $exportData['updated_at']);

        $filename = 'white-label-settings-' . date('Y-m-d-H-i-s') . '.json';

        return response()->json($exportData)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings_file' => 'required|file|mimes:json|max:1024',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $content = file_get_contents($request->file('settings_file')->getRealPath());
            $importData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Arquivo JSON inválido.');
            }

            $tenantId = auth()->user()->tenant_id;
            $settings = WhiteLabelSetting::firstOrCreate(
                ['tenant_id' => $tenantId],
                WhiteLabelSetting::getDefaultSettings()
            );

            // Filter only allowed fields
            $allowedFields = [
                'primary_color', 'secondary_color', 'accent_color', 'background_color', 'text_color',
                'company_name', 'app_name', 'tagline', 'footer_text', 'dashboard_welcome_message',
                'meta_title', 'meta_description', 'meta_keywords', 'custom_css', 'custom_js',
                'email_template_header', 'email_template_footer', 'social_links', 'contact_info',
                'features_enabled', 'modules_config', 'theme_config'
            ];

            $filteredData = array_intersect_key($importData, array_flip($allowedFields));
            $settings->update($filteredData);

            return back()->with('success', 'Configurações importadas com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao importar configurações: ' . $e->getMessage());
        }
    }

    private function getColorPresets()
    {
        return [
            'default' => [
                'name' => 'Padrão',
                'primary' => '#3B82F6',
                'secondary' => '#6B7280',
                'accent' => '#10B981',
            ],
            'purple' => [
                'name' => 'Roxo',
                'primary' => '#8B5CF6',
                'secondary' => '#6B7280',
                'accent' => '#F59E0B',
            ],
            'green' => [
                'name' => 'Verde',
                'primary' => '#10B981',
                'secondary' => '#6B7280',
                'accent' => '#3B82F6',
            ],
            'red' => [
                'name' => 'Vermelho',
                'primary' => '#EF4444',
                'secondary' => '#6B7280',
                'accent' => '#F59E0B',
            ],
            'orange' => [
                'name' => 'Laranja',
                'primary' => '#F97316',
                'secondary' => '#6B7280',
                'accent' => '#10B981',
            ],
            'pink' => [
                'name' => 'Rosa',
                'primary' => '#EC4899',
                'secondary' => '#6B7280',
                'accent' => '#8B5CF6',
            ],
        ];
    }

    private function getThemeOptions()
    {
        return [
            'layout' => [
                'sidebar' => 'Sidebar',
                'topbar' => 'Barra Superior',
                'mixed' => 'Misto',
            ],
            'sidebar_position' => [
                'left' => 'Esquerda',
                'right' => 'Direita',
            ],
        ];
    }

    private function getModuleOptions()
    {
        return [
            'financial' => 'Módulo Financeiro',
            'users' => 'Gerenciamento de Usuários',
            'reports' => 'Relatórios',
            'settings' => 'Configurações',
            'analytics' => 'Analytics',
            'notifications' => 'Notificações',
            'api' => 'API',
            'integrations' => 'Integrações',
        ];
    }

    private function generatePreviewCss($settings)
    {
        $css = ":root {\n";
        $css .= "  --color-primary: {$settings['primary_color']};\n";
        $css .= "  --color-secondary: {$settings['secondary_color']};\n";
        $css .= "  --color-accent: {$settings['accent_color']};\n";
        $css .= "  --color-background: {$settings['background_color']};\n";
        $css .= "  --color-text: {$settings['text_color']};\n";
        $css .= "}\n";

        if (!empty($settings['custom_css'])) {
            $css .= "\n" . $settings['custom_css'];
        }

        return $css;
    }
}