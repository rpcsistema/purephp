<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhiteLabelSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'logo_url',
        'favicon_url',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'text_color',
        'company_name',
        'app_name',
        'tagline',
        'footer_text',
        'custom_css',
        'custom_js',
        'email_template_header',
        'email_template_footer',
        'login_background_url',
        'dashboard_welcome_message',
        'sidebar_logo_url',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'social_links',
        'contact_info',
        'features_enabled',
        'modules_config',
        'theme_config',
        'is_active',
    ];

    protected $casts = [
        'social_links' => 'array',
        'contact_info' => 'array',
        'features_enabled' => 'array',
        'modules_config' => 'array',
        'theme_config' => 'array',
        'is_active' => 'boolean',
    ];

    // Relacionamentos
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Accessors
    public function getLogoUrlAttribute($value)
    {
        if (!$value) {
            return asset('images/default-logo.png');
        }
        
        return str_starts_with($value, 'http') ? $value : asset('storage/' . $value);
    }

    public function getFaviconUrlAttribute($value)
    {
        if (!$value) {
            return asset('images/default-favicon.ico');
        }
        
        return str_starts_with($value, 'http') ? $value : asset('storage/' . $value);
    }

    public function getSidebarLogoUrlAttribute($value)
    {
        if (!$value) {
            return $this->logo_url;
        }
        
        return str_starts_with($value, 'http') ? $value : asset('storage/' . $value);
    }

    public function getLoginBackgroundUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        return str_starts_with($value, 'http') ? $value : asset('storage/' . $value);
    }

    // Métodos auxiliares
    public function getColorPalette()
    {
        return [
            'primary' => $this->primary_color ?: '#3B82F6',
            'secondary' => $this->secondary_color ?: '#6B7280',
            'accent' => $this->accent_color ?: '#10B981',
            'background' => $this->background_color ?: '#FFFFFF',
            'text' => $this->text_color ?: '#1F2937',
        ];
    }

    public function getEnabledModules()
    {
        $defaultModules = [
            'financial' => true,
            'users' => true,
            'reports' => true,
            'settings' => true,
        ];

        return array_merge($defaultModules, $this->modules_config ?: []);
    }

    public function isModuleEnabled($module)
    {
        $enabledModules = $this->getEnabledModules();
        return $enabledModules[$module] ?? false;
    }

    public function getThemeConfiguration()
    {
        $defaultTheme = [
            'layout' => 'sidebar',
            'sidebar_position' => 'left',
            'header_fixed' => true,
            'sidebar_collapsed' => false,
            'dark_mode' => false,
            'rounded_corners' => true,
            'animations' => true,
        ];

        return array_merge($defaultTheme, $this->theme_config ?: []);
    }

    public function getSocialLinks()
    {
        return $this->social_links ?: [];
    }

    public function getContactInfo()
    {
        $defaultContact = [
            'email' => null,
            'phone' => null,
            'address' => null,
            'website' => null,
        ];

        return array_merge($defaultContact, $this->contact_info ?: []);
    }

    public function generateCssVariables()
    {
        $colors = $this->getColorPalette();
        
        $css = ":root {\n";
        foreach ($colors as $name => $color) {
            $css .= "  --color-{$name}: {$color};\n";
        }
        $css .= "}\n";

        if ($this->custom_css) {
            $css .= "\n" . $this->custom_css;
        }

        return $css;
    }

    public function getEmailTemplateHeader()
    {
        return $this->email_template_header ?: $this->getDefaultEmailHeader();
    }

    public function getEmailTemplateFooter()
    {
        return $this->email_template_footer ?: $this->getDefaultEmailFooter();
    }

    private function getDefaultEmailHeader()
    {
        return '
        <div style="background-color: ' . $this->primary_color . '; padding: 20px; text-align: center;">
            <img src="' . $this->logo_url . '" alt="' . $this->company_name . '" style="max-height: 50px;">
        </div>';
    }

    private function getDefaultEmailFooter()
    {
        $contact = $this->getContactInfo();
        
        return '
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d;">
            <p>' . ($this->footer_text ?: '© ' . date('Y') . ' ' . $this->company_name . '. Todos os direitos reservados.') . '</p>
            ' . ($contact['email'] ? '<p>Email: ' . $contact['email'] . '</p>' : '') . '
            ' . ($contact['phone'] ? '<p>Telefone: ' . $contact['phone'] . '</p>' : '') . '
        </div>';
    }

    // Métodos estáticos
    public static function getForTenant($tenantId)
    {
        return static::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }

    public static function getDefaultSettings()
    {
        return [
            'primary_color' => '#3B82F6',
            'secondary_color' => '#6B7280',
            'accent_color' => '#10B981',
            'background_color' => '#FFFFFF',
            'text_color' => '#1F2937',
            'company_name' => 'SaaS Platform',
            'app_name' => 'Dashboard',
            'tagline' => 'Sua plataforma de gestão',
            'footer_text' => '© ' . date('Y') . ' SaaS Platform. Todos os direitos reservados.',
            'dashboard_welcome_message' => 'Bem-vindo ao seu dashboard!',
            'meta_title' => 'SaaS Platform - Dashboard',
            'meta_description' => 'Plataforma SaaS para gestão empresarial',
            'features_enabled' => [
                'financial' => true,
                'users' => true,
                'reports' => true,
                'settings' => true,
            ],
            'theme_config' => [
                'layout' => 'sidebar',
                'sidebar_position' => 'left',
                'header_fixed' => true,
                'sidebar_collapsed' => false,
                'dark_mode' => false,
                'rounded_corners' => true,
                'animations' => true,
            ],
            'is_active' => true,
        ];
    }
}