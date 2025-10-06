import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function WhiteLabelIndex({ settings, colorPresets, themeOptions, moduleOptions }) {
    const [activeTab, setActiveTab] = useState('branding');
    const [previewMode, setPreviewMode] = useState(false);
    const [previewData, setPreviewData] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        // Branding
        company_name: settings.company_name || '',
        app_name: settings.app_name || '',
        tagline: settings.tagline || '',
        footer_text: settings.footer_text || '',
        dashboard_welcome_message: settings.dashboard_welcome_message || '',
        
        // Colors
        primary_color: settings.primary_color || '#3B82F6',
        secondary_color: settings.secondary_color || '#6B7280',
        accent_color: settings.accent_color || '#10B981',
        background_color: settings.background_color || '#FFFFFF',
        text_color: settings.text_color || '#1F2937',
        
        // SEO
        meta_title: settings.meta_title || '',
        meta_description: settings.meta_description || '',
        meta_keywords: settings.meta_keywords || '',
        
        // Custom Code
        custom_css: settings.custom_css || '',
        custom_js: settings.custom_js || '',
        
        // Email Templates
        email_template_header: settings.email_template_header || '',
        email_template_footer: settings.email_template_footer || '',
        
        // Social Links
        social_links: settings.social_links || {
            facebook: '',
            twitter: '',
            instagram: '',
            linkedin: '',
            youtube: ''
        },
        
        // Contact Info
        contact_info: settings.contact_info || {
            email: '',
            phone: '',
            address: '',
            website: ''
        },
        
        // Features
        features_enabled: settings.features_enabled || {},
        modules_config: settings.modules_config || {},
        theme_config: settings.theme_config || {
            layout: 'sidebar',
            sidebar_position: 'left',
            header_fixed: true,
            sidebar_collapsed: false,
            dark_mode: false,
            rounded_corners: true,
            animations: true
        },
        
        // Files
        logo: null,
        favicon: null,
        sidebar_logo: null,
        login_background: null,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('white-label.update'), {
            forceFormData: true,
        });
    };

    const handlePreview = async () => {
        try {
            const response = await fetch(route('white-label.preview'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(data),
            });
            
            const result = await response.json();
            setPreviewData(result);
            setPreviewMode(true);
        } catch (error) {
            console.error('Erro ao gerar preview:', error);
        }
    };

    const handleReset = () => {
        if (confirm('Tem certeza que deseja resetar todas as configurações?')) {
            post(route('white-label.reset'));
        }
    };

    const handleExport = () => {
        window.location.href = route('white-label.export');
    };

    const applyColorPreset = (preset) => {
        setData({
            ...data,
            primary_color: preset.primary,
            secondary_color: preset.secondary,
            accent_color: preset.accent,
        });
    };

    const tabs = [
        { id: 'branding', name: 'Marca', icon: '🎨' },
        { id: 'colors', name: 'Cores', icon: '🌈' },
        { id: 'layout', name: 'Layout', icon: '📐' },
        { id: 'modules', name: 'Módulos', icon: '🧩' },
        { id: 'seo', name: 'SEO', icon: '🔍' },
        { id: 'social', name: 'Social', icon: '📱' },
        { id: 'email', name: 'Email', icon: '📧' },
        { id: 'advanced', name: 'Avançado', icon: '⚙️' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Configurações White Label" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Configurações White Label</h1>
                        <p className="mt-1 text-sm text-gray-600">
                            Personalize a aparência e funcionalidades da sua plataforma
                        </p>
                    </div>

                    {/* Action Buttons */}
                    <div className="mb-6 flex flex-wrap gap-3">
                        <button
                            type="button"
                            onClick={handlePreview}
                            className="btn-secondary"
                        >
                            👁️ Visualizar
                        </button>
                        <button
                            type="button"
                            onClick={handleExport}
                            className="btn-secondary"
                        >
                            📥 Exportar
                        </button>
                        <label className="btn-secondary cursor-pointer">
                            📤 Importar
                            <input
                                type="file"
                                accept=".json"
                                className="hidden"
                                onChange={(e) => {
                                    const file = e.target.files[0];
                                    if (file) {
                                        const formData = new FormData();
                                        formData.append('settings_file', file);
                                        post(route('white-label.import'), {
                                            data: formData,
                                            forceFormData: true,
                                        });
                                    }
                                }}
                            />
                        </label>
                        <button
                            type="button"
                            onClick={handleReset}
                            className="btn-danger"
                        >
                            🔄 Resetar
                        </button>
                    </div>

                    <div className="bg-white shadow rounded-lg">
                        {/* Tabs */}
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8 px-6">
                                {tabs.map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => setActiveTab(tab.id)}
                                        className={`py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap ${
                                            activeTab === tab.id
                                                ? 'border-blue-500 text-blue-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }`}
                                    >
                                        <span className="mr-2">{tab.icon}</span>
                                        {tab.name}
                                    </button>
                                ))}
                            </nav>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6">
                            {/* Branding Tab */}
                            {activeTab === 'branding' && (
                                <div className="space-y-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Nome da Empresa
                                            </label>
                                            <input
                                                type="text"
                                                value={data.company_name}
                                                onChange={(e) => setData('company_name', e.target.value)}
                                                className="form-input"
                                                placeholder="Minha Empresa"
                                            />
                                            {errors.company_name && (
                                                <p className="mt-1 text-sm text-red-600">{errors.company_name}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Nome do App
                                            </label>
                                            <input
                                                type="text"
                                                value={data.app_name}
                                                onChange={(e) => setData('app_name', e.target.value)}
                                                className="form-input"
                                                placeholder="Dashboard"
                                            />
                                            {errors.app_name && (
                                                <p className="mt-1 text-sm text-red-600">{errors.app_name}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Slogan
                                        </label>
                                        <input
                                            type="text"
                                            value={data.tagline}
                                            onChange={(e) => setData('tagline', e.target.value)}
                                            className="form-input"
                                            placeholder="Sua plataforma de gestão"
                                        />
                                        {errors.tagline && (
                                            <p className="mt-1 text-sm text-red-600">{errors.tagline}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Mensagem de Boas-vindas do Dashboard
                                        </label>
                                        <textarea
                                            value={data.dashboard_welcome_message}
                                            onChange={(e) => setData('dashboard_welcome_message', e.target.value)}
                                            rows={3}
                                            className="form-input"
                                            placeholder="Bem-vindo ao seu dashboard!"
                                        />
                                        {errors.dashboard_welcome_message && (
                                            <p className="mt-1 text-sm text-red-600">{errors.dashboard_welcome_message}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Texto do Rodapé
                                        </label>
                                        <textarea
                                            value={data.footer_text}
                                            onChange={(e) => setData('footer_text', e.target.value)}
                                            rows={2}
                                            className="form-input"
                                            placeholder="© 2024 Minha Empresa. Todos os direitos reservados."
                                        />
                                        {errors.footer_text && (
                                            <p className="mt-1 text-sm text-red-600">{errors.footer_text}</p>
                                        )}
                                    </div>

                                    {/* File Uploads */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Logo Principal
                                            </label>
                                            <input
                                                type="file"
                                                accept="image/*"
                                                onChange={(e) => setData('logo', e.target.files[0])}
                                                className="form-input"
                                            />
                                            {settings.logo_url && (
                                                <img
                                                    src={settings.logo_url}
                                                    alt="Logo atual"
                                                    className="mt-2 h-12 object-contain"
                                                />
                                            )}
                                            {errors.logo && (
                                                <p className="mt-1 text-sm text-red-600">{errors.logo}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Favicon
                                            </label>
                                            <input
                                                type="file"
                                                accept=".ico,.png"
                                                onChange={(e) => setData('favicon', e.target.files[0])}
                                                className="form-input"
                                            />
                                            {settings.favicon_url && (
                                                <img
                                                    src={settings.favicon_url}
                                                    alt="Favicon atual"
                                                    className="mt-2 h-8 w-8 object-contain"
                                                />
                                            )}
                                            {errors.favicon && (
                                                <p className="mt-1 text-sm text-red-600">{errors.favicon}</p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Colors Tab */}
                            {activeTab === 'colors' && (
                                <div className="space-y-6">
                                    {/* Color Presets */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-3">
                                            Presets de Cores
                                        </label>
                                        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                                            {Object.entries(colorPresets).map(([key, preset]) => (
                                                <button
                                                    key={key}
                                                    type="button"
                                                    onClick={() => applyColorPreset(preset)}
                                                    className="p-3 border rounded-lg hover:shadow-md transition-shadow"
                                                >
                                                    <div className="flex space-x-1 mb-2">
                                                        <div
                                                            className="w-4 h-4 rounded"
                                                            style={{ backgroundColor: preset.primary }}
                                                        />
                                                        <div
                                                            className="w-4 h-4 rounded"
                                                            style={{ backgroundColor: preset.secondary }}
                                                        />
                                                        <div
                                                            className="w-4 h-4 rounded"
                                                            style={{ backgroundColor: preset.accent }}
                                                        />
                                                    </div>
                                                    <span className="text-xs font-medium">{preset.name}</span>
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Color Inputs */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        {[
                                            { key: 'primary_color', label: 'Cor Primária' },
                                            { key: 'secondary_color', label: 'Cor Secundária' },
                                            { key: 'accent_color', label: 'Cor de Destaque' },
                                            { key: 'background_color', label: 'Cor de Fundo' },
                                            { key: 'text_color', label: 'Cor do Texto' },
                                        ].map((color) => (
                                            <div key={color.key}>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    {color.label}
                                                </label>
                                                <div className="flex items-center space-x-3">
                                                    <input
                                                        type="color"
                                                        value={data[color.key]}
                                                        onChange={(e) => setData(color.key, e.target.value)}
                                                        className="w-12 h-10 border border-gray-300 rounded cursor-pointer"
                                                    />
                                                    <input
                                                        type="text"
                                                        value={data[color.key]}
                                                        onChange={(e) => setData(color.key, e.target.value)}
                                                        className="form-input flex-1"
                                                        placeholder="#000000"
                                                    />
                                                </div>
                                                {errors[color.key] && (
                                                    <p className="mt-1 text-sm text-red-600">{errors[color.key]}</p>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Layout Tab */}
                            {activeTab === 'layout' && (
                                <div className="space-y-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Tipo de Layout
                                            </label>
                                            <select
                                                value={data.theme_config.layout}
                                                onChange={(e) => setData('theme_config', {
                                                    ...data.theme_config,
                                                    layout: e.target.value
                                                })}
                                                className="form-input"
                                            >
                                                {Object.entries(themeOptions.layout).map(([value, label]) => (
                                                    <option key={value} value={value}>{label}</option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Posição da Sidebar
                                            </label>
                                            <select
                                                value={data.theme_config.sidebar_position}
                                                onChange={(e) => setData('theme_config', {
                                                    ...data.theme_config,
                                                    sidebar_position: e.target.value
                                                })}
                                                className="form-input"
                                            >
                                                {Object.entries(themeOptions.sidebar_position).map(([value, label]) => (
                                                    <option key={value} value={value}>{label}</option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>

                                    {/* Theme Options */}
                                    <div className="space-y-4">
                                        <h3 className="text-lg font-medium text-gray-900">Opções de Tema</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {[
                                                { key: 'header_fixed', label: 'Cabeçalho Fixo' },
                                                { key: 'sidebar_collapsed', label: 'Sidebar Recolhida' },
                                                { key: 'dark_mode', label: 'Modo Escuro' },
                                                { key: 'rounded_corners', label: 'Cantos Arredondados' },
                                                { key: 'animations', label: 'Animações' },
                                            ].map((option) => (
                                                <label key={option.key} className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={data.theme_config[option.key] || false}
                                                        onChange={(e) => setData('theme_config', {
                                                            ...data.theme_config,
                                                            [option.key]: e.target.checked
                                                        })}
                                                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    />
                                                    <span className="ml-2 text-sm text-gray-700">{option.label}</span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Modules Tab */}
                            {activeTab === 'modules' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">Módulos Habilitados</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {Object.entries(moduleOptions).map(([key, label]) => (
                                                <label key={key} className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={data.features_enabled[key] || false}
                                                        onChange={(e) => setData('features_enabled', {
                                                            ...data.features_enabled,
                                                            [key]: e.target.checked
                                                        })}
                                                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    />
                                                    <span className="ml-2 text-sm text-gray-700">{label}</span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* SEO Tab */}
                            {activeTab === 'seo' && (
                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Título Meta
                                        </label>
                                        <input
                                            type="text"
                                            value={data.meta_title}
                                            onChange={(e) => setData('meta_title', e.target.value)}
                                            className="form-input"
                                            placeholder="Minha Plataforma SaaS"
                                        />
                                        {errors.meta_title && (
                                            <p className="mt-1 text-sm text-red-600">{errors.meta_title}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Descrição Meta
                                        </label>
                                        <textarea
                                            value={data.meta_description}
                                            onChange={(e) => setData('meta_description', e.target.value)}
                                            rows={3}
                                            className="form-input"
                                            placeholder="Descrição da sua plataforma para mecanismos de busca"
                                        />
                                        {errors.meta_description && (
                                            <p className="mt-1 text-sm text-red-600">{errors.meta_description}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Palavras-chave Meta
                                        </label>
                                        <input
                                            type="text"
                                            value={data.meta_keywords}
                                            onChange={(e) => setData('meta_keywords', e.target.value)}
                                            className="form-input"
                                            placeholder="saas, plataforma, gestão, dashboard"
                                        />
                                        {errors.meta_keywords && (
                                            <p className="mt-1 text-sm text-red-600">{errors.meta_keywords}</p>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Social Tab */}
                            {activeTab === 'social' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">Redes Sociais</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            {Object.entries(data.social_links).map(([platform, url]) => (
                                                <div key={platform}>
                                                    <label className="block text-sm font-medium text-gray-700 mb-2 capitalize">
                                                        {platform}
                                                    </label>
                                                    <input
                                                        type="url"
                                                        value={url}
                                                        onChange={(e) => setData('social_links', {
                                                            ...data.social_links,
                                                            [platform]: e.target.value
                                                        })}
                                                        className="form-input"
                                                        placeholder={`https://${platform}.com/suaempresa`}
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">Informações de Contato</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Email
                                                </label>
                                                <input
                                                    type="email"
                                                    value={data.contact_info.email}
                                                    onChange={(e) => setData('contact_info', {
                                                        ...data.contact_info,
                                                        email: e.target.value
                                                    })}
                                                    className="form-input"
                                                    placeholder="contato@suaempresa.com"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Telefone
                                                </label>
                                                <input
                                                    type="tel"
                                                    value={data.contact_info.phone}
                                                    onChange={(e) => setData('contact_info', {
                                                        ...data.contact_info,
                                                        phone: e.target.value
                                                    })}
                                                    className="form-input"
                                                    placeholder="(11) 99999-9999"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Website
                                                </label>
                                                <input
                                                    type="url"
                                                    value={data.contact_info.website}
                                                    onChange={(e) => setData('contact_info', {
                                                        ...data.contact_info,
                                                        website: e.target.value
                                                    })}
                                                    className="form-input"
                                                    placeholder="https://suaempresa.com"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Endereço
                                                </label>
                                                <textarea
                                                    value={data.contact_info.address}
                                                    onChange={(e) => setData('contact_info', {
                                                        ...data.contact_info,
                                                        address: e.target.value
                                                    })}
                                                    rows={2}
                                                    className="form-input"
                                                    placeholder="Rua Example, 123 - São Paulo, SP"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Email Tab */}
                            {activeTab === 'email' && (
                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Cabeçalho do Email (HTML)
                                        </label>
                                        <textarea
                                            value={data.email_template_header}
                                            onChange={(e) => setData('email_template_header', e.target.value)}
                                            rows={6}
                                            className="form-input font-mono text-sm"
                                            placeholder="<div style='background-color: #3B82F6; padding: 20px;'>..."
                                        />
                                        {errors.email_template_header && (
                                            <p className="mt-1 text-sm text-red-600">{errors.email_template_header}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Rodapé do Email (HTML)
                                        </label>
                                        <textarea
                                            value={data.email_template_footer}
                                            onChange={(e) => setData('email_template_footer', e.target.value)}
                                            rows={6}
                                            className="form-input font-mono text-sm"
                                            placeholder="<div style='background-color: #f8f9fa; padding: 20px;'>..."
                                        />
                                        {errors.email_template_footer && (
                                            <p className="mt-1 text-sm text-red-600">{errors.email_template_footer}</p>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Advanced Tab */}
                            {activeTab === 'advanced' && (
                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            CSS Personalizado
                                        </label>
                                        <textarea
                                            value={data.custom_css}
                                            onChange={(e) => setData('custom_css', e.target.value)}
                                            rows={10}
                                            className="form-input font-mono text-sm"
                                            placeholder=".custom-class { color: red; }"
                                        />
                                        {errors.custom_css && (
                                            <p className="mt-1 text-sm text-red-600">{errors.custom_css}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            JavaScript Personalizado
                                        </label>
                                        <textarea
                                            value={data.custom_js}
                                            onChange={(e) => setData('custom_js', e.target.value)}
                                            rows={10}
                                            className="form-input font-mono text-sm"
                                            placeholder="console.log('Custom JS');"
                                        />
                                        {errors.custom_js && (
                                            <p className="mt-1 text-sm text-red-600">{errors.custom_js}</p>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Submit Button */}
                            <div className="flex justify-end pt-6 border-t border-gray-200">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="btn-primary"
                                >
                                    {processing ? 'Salvando...' : 'Salvar Configurações'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {/* Preview Modal */}
            {previewMode && previewData && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg max-w-4xl max-h-[90vh] overflow-auto">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-medium">Preview das Configurações</h3>
                                <button
                                    onClick={() => setPreviewMode(false)}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    ✕
                                </button>
                            </div>
                            
                            <div className="border rounded-lg p-4">
                                <style dangerouslySetInnerHTML={{ __html: previewData.css }} />
                                <div style={{ 
                                    backgroundColor: previewData.settings.background_color,
                                    color: previewData.settings.text_color,
                                    padding: '20px',
                                    borderRadius: '8px'
                                }}>
                                    <h1 style={{ color: previewData.settings.primary_color }}>
                                        {previewData.settings.company_name || 'Nome da Empresa'}
                                    </h1>
                                    <p style={{ color: previewData.settings.secondary_color }}>
                                        {previewData.settings.tagline || 'Slogan da empresa'}
                                    </p>
                                    <button style={{ 
                                        backgroundColor: previewData.settings.primary_color,
                                        color: 'white',
                                        padding: '8px 16px',
                                        borderRadius: '4px',
                                        border: 'none',
                                        marginRight: '8px'
                                    }}>
                                        Botão Primário
                                    </button>
                                    <button style={{ 
                                        backgroundColor: previewData.settings.accent_color,
                                        color: 'white',
                                        padding: '8px 16px',
                                        borderRadius: '4px',
                                        border: 'none'
                                    }}>
                                        Botão Destaque
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}