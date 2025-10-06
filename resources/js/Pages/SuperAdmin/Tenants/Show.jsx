import React from 'react';
import { Head, Link } from '@inertiajs/react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';
import {
    ArrowLeftIcon,
    PencilIcon,
    UserGroupIcon,
    CalendarIcon,
    GlobeAltIcon,
    CogIcon,
} from '@heroicons/react/24/outline';

export default function Show({ auth, tenant }) {
    const getStatusBadge = (status) => {
        const badges = {
            active: 'bg-green-100 text-green-800',
            paused: 'bg-yellow-100 text-yellow-800',
            inactive: 'bg-red-100 text-red-800',
        };
        
        const labels = {
            active: 'Ativo',
            paused: 'Pausado',
            inactive: 'Inativo',
        };

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badges[status]}`}>
                {labels[status]}
            </span>
        );
    };

    return (
        <SuperAdminLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href="/super-admin/tenants"
                            className="text-gray-500 hover:text-gray-700"
                        >
                            <ArrowLeftIcon className="h-6 w-6" />
                        </Link>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Detalhes do Tenant
                        </h2>
                    </div>
                    <Link
                        href={`/super-admin/tenants/${tenant.id}/edit`}
                        className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                        <PencilIcon className="-ml-1 mr-2 h-5 w-5" />
                        Editar
                    </Link>
                </div>
            }
        >
            <Head title={`Tenant: ${tenant.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {/* Header do Tenant */}
                            <div className="border-b border-gray-200 pb-6 mb-6">
                                <div className="flex items-center space-x-4">
                                    <div className="flex-shrink-0 h-16 w-16">
                                        <div className="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span className="text-xl font-medium text-gray-700">
                                                {tenant.name.charAt(0).toUpperCase()}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="flex-1">
                                        <h3 className="text-2xl font-bold text-gray-900">{tenant.name}</h3>
                                        <p className="text-sm text-gray-500">{tenant.email}</p>
                                        <div className="mt-2">
                                            {getStatusBadge(tenant.status)}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                {/* Informações Básicas */}
                                <div className="lg:col-span-2">
                                    <div className="bg-gray-50 rounded-lg p-6">
                                        <h4 className="text-lg font-medium text-gray-900 mb-4">
                                            Informações Básicas
                                        </h4>
                                        <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Nome da Empresa</dt>
                                                <dd className="mt-1 text-sm text-gray-900">{tenant.name}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Email</dt>
                                                <dd className="mt-1 text-sm text-gray-900">{tenant.email}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Domínio</dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    {tenant.domain || 'Não configurado'}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Status</dt>
                                                <dd className="mt-1">{getStatusBadge(tenant.status)}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Criado em</dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    {new Date(tenant.created_at).toLocaleDateString('pt-BR', {
                                                        year: 'numeric',
                                                        month: 'long',
                                                        day: 'numeric',
                                                        hour: '2-digit',
                                                        minute: '2-digit'
                                                    })}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Última atualização</dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    {new Date(tenant.updated_at).toLocaleDateString('pt-BR', {
                                                        year: 'numeric',
                                                        month: 'long',
                                                        day: 'numeric',
                                                        hour: '2-digit',
                                                        minute: '2-digit'
                                                    })}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>

                                    {/* Configurações */}
                                    {tenant.settings && (
                                        <div className="mt-6 bg-gray-50 rounded-lg p-6">
                                            <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                                <CogIcon className="h-5 w-5 mr-2" />
                                                Configurações
                                            </h4>
                                            <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                                <div>
                                                    <dt className="text-sm font-medium text-gray-500">Tema</dt>
                                                    <dd className="mt-1 text-sm text-gray-900 capitalize">
                                                        {tenant.settings.theme || 'Padrão'}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="text-sm font-medium text-gray-500">Fuso Horário</dt>
                                                    <dd className="mt-1 text-sm text-gray-900">
                                                        {tenant.settings.timezone || 'America/Sao_Paulo'}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="text-sm font-medium text-gray-500">Idioma</dt>
                                                    <dd className="mt-1 text-sm text-gray-900">
                                                        {tenant.settings.language || 'pt-BR'}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="text-sm font-medium text-gray-500">Moeda</dt>
                                                    <dd className="mt-1 text-sm text-gray-900">
                                                        {tenant.settings.currency || 'BRL'}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                    )}
                                </div>

                                {/* Estatísticas */}
                                <div className="space-y-6">
                                    {/* Usuários */}
                                    <div className="bg-white border border-gray-200 rounded-lg p-6">
                                        <div className="flex items-center">
                                            <div className="flex-shrink-0">
                                                <UserGroupIcon className="h-8 w-8 text-blue-500" />
                                            </div>
                                            <div className="ml-4">
                                                <div className="text-sm font-medium text-gray-500">Usuários</div>
                                                <div className="text-2xl font-bold text-gray-900">
                                                    {tenant.users_count || 0}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Ações Rápidas */}
                                    <div className="bg-gray-50 rounded-lg p-6">
                                        <h4 className="text-lg font-medium text-gray-900 mb-4">
                                            Ações Rápidas
                                        </h4>
                                        <div className="space-y-3">
                                            <Link
                                                href={`/super-admin/tenants/${tenant.id}/edit`}
                                                className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md"
                                            >
                                                <PencilIcon className="h-4 w-4 inline mr-2" />
                                                Editar Tenant
                                            </Link>
                                            <Link
                                                href={`/super-admin/tenants/${tenant.id}/users`}
                                                className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md"
                                            >
                                                <UserGroupIcon className="h-4 w-4 inline mr-2" />
                                                Gerenciar Usuários
                                            </Link>
                                            {tenant.domain && (
                                                <a
                                                    href={`https://${tenant.domain}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md"
                                                >
                                                    <GlobeAltIcon className="h-4 w-4 inline mr-2" />
                                                    Visitar Site
                                                </a>
                                            )}
                                        </div>
                                    </div>

                                    {/* Histórico */}
                                    <div className="bg-gray-50 rounded-lg p-6">
                                        <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                            <CalendarIcon className="h-5 w-5 mr-2" />
                                            Histórico
                                        </h4>
                                        <div className="space-y-3 text-sm">
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Criado:</span>
                                                <span className="text-gray-900">
                                                    {new Date(tenant.created_at).toLocaleDateString('pt-BR')}
                                                </span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Última atualização:</span>
                                                <span className="text-gray-900">
                                                    {new Date(tenant.updated_at).toLocaleDateString('pt-BR')}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SuperAdminLayout>
    );
}