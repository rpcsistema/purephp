import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

export default function Edit({ auth, tenant }) {
    const { data, setData, put, processing, errors } = useForm({
        name: tenant.name || '',
        email: tenant.email || '',
        domain: tenant.domain || '',
        status: tenant.status || 'active',
        settings: {
            theme: tenant.settings?.theme || 'default',
            timezone: tenant.settings?.timezone || 'America/Sao_Paulo',
            language: tenant.settings?.language || 'pt-BR',
            currency: tenant.settings?.currency || 'BRL',
        },
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/super-admin/tenants/${tenant.id}`);
    };

    const handleSettingChange = (key, value) => {
        setData('settings', {
            ...data.settings,
            [key]: value,
        });
    };

    return (
        <SuperAdminLayout
            user={auth.user}
            header={
                <div className="flex items-center space-x-4">
                    <Link
                        href={`/super-admin/tenants/${tenant.id}`}
                        className="text-gray-500 hover:text-gray-700"
                    >
                        <ArrowLeftIcon className="h-6 w-6" />
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Editar Tenant: {tenant.name}
                    </h2>
                </div>
            }
        >
            <Head title={`Editar Tenant: ${tenant.name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Informações Básicas */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4">
                                    Informações Básicas
                                </h3>
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <div>
                                        <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                            Nome da Empresa *
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required
                                        />
                                        {errors.name && (
                                            <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                            Email do Administrador *
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required
                                        />
                                        {errors.email && (
                                            <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="domain" className="block text-sm font-medium text-gray-700">
                                            Domínio
                                        </label>
                                        <input
                                            type="text"
                                            id="domain"
                                            value={data.domain}
                                            onChange={(e) => setData('domain', e.target.value)}
                                            placeholder="exemplo.com"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.domain && (
                                            <p className="mt-1 text-sm text-red-600">{errors.domain}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="status" className="block text-sm font-medium text-gray-700">
                                            Status *
                                        </label>
                                        <select
                                            id="status"
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="active">Ativo</option>
                                            <option value="paused">Pausado</option>
                                            <option value="inactive">Inativo</option>
                                        </select>
                                        {errors.status && (
                                            <p className="mt-1 text-sm text-red-600">{errors.status}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Configurações Iniciais */}
                            <div className="border-t border-gray-200 pt-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">
                                    Configurações do Sistema
                                </h3>
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <div>
                                        <label htmlFor="theme" className="block text-sm font-medium text-gray-700">
                                            Tema
                                        </label>
                                        <select
                                            id="theme"
                                            value={data.settings.theme}
                                            onChange={(e) => handleSettingChange('theme', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="default">Padrão</option>
                                            <option value="dark">Escuro</option>
                                            <option value="light">Claro</option>
                                            <option value="blue">Azul</option>
                                            <option value="green">Verde</option>
                                        </select>
                                        {errors['settings.theme'] && (
                                            <p className="mt-1 text-sm text-red-600">{errors['settings.theme']}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="timezone" className="block text-sm font-medium text-gray-700">
                                            Fuso Horário
                                        </label>
                                        <select
                                            id="timezone"
                                            value={data.settings.timezone}
                                            onChange={(e) => handleSettingChange('timezone', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="America/Sao_Paulo">São Paulo (GMT-3)</option>
                                            <option value="America/New_York">Nova York (GMT-5)</option>
                                            <option value="Europe/London">Londres (GMT+0)</option>
                                            <option value="Europe/Paris">Paris (GMT+1)</option>
                                            <option value="Asia/Tokyo">Tóquio (GMT+9)</option>
                                        </select>
                                        {errors['settings.timezone'] && (
                                            <p className="mt-1 text-sm text-red-600">{errors['settings.timezone']}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="language" className="block text-sm font-medium text-gray-700">
                                            Idioma
                                        </label>
                                        <select
                                            id="language"
                                            value={data.settings.language}
                                            onChange={(e) => handleSettingChange('language', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="pt-BR">Português (Brasil)</option>
                                            <option value="en-US">English (US)</option>
                                            <option value="es-ES">Español</option>
                                            <option value="fr-FR">Français</option>
                                        </select>
                                        {errors['settings.language'] && (
                                            <p className="mt-1 text-sm text-red-600">{errors['settings.language']}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="currency" className="block text-sm font-medium text-gray-700">
                                            Moeda
                                        </label>
                                        <select
                                            id="currency"
                                            value={data.settings.currency}
                                            onChange={(e) => handleSettingChange('currency', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="BRL">Real Brasileiro (R$)</option>
                                            <option value="USD">Dólar Americano ($)</option>
                                            <option value="EUR">Euro (€)</option>
                                            <option value="GBP">Libra Esterlina (£)</option>
                                        </select>
                                        {errors['settings.currency'] && (
                                            <p className="mt-1 text-sm text-red-600">{errors['settings.currency']}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Botões de Ação */}
                            <div className="border-t border-gray-200 pt-6">
                                <div className="flex items-center justify-end space-x-4">
                                    <Link
                                        href={`/super-admin/tenants/${tenant.id}`}
                                        className="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Cancelar
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                    >
                                        {processing ? 'Salvando...' : 'Salvar Alterações'}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </SuperAdminLayout>
    );
}