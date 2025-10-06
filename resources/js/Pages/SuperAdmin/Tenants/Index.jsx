import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    PlayIcon,
    PauseIcon,
} from '@heroicons/react/24/outline';

export default function Index({ auth, tenants, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/super-admin/tenants', { search, status }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleStatusChange = (value) => {
        setStatus(value);
        router.get('/super-admin/tenants', { search, status: value }, {
            preserveState: true,
            replace: true,
        });
    };

    const toggleTenantStatus = (tenant) => {
        if (confirm(`Tem certeza que deseja ${tenant.status === 'active' ? 'pausar' : 'ativar'} este tenant?`)) {
            router.post(`/super-admin/tenants/${tenant.id}/toggle-status`, {}, {
                preserveScroll: true,
            });
        }
    };

    const deleteTenant = (tenant) => {
        if (confirm('Tem certeza que deseja excluir este tenant? Esta ação não pode ser desfeita.')) {
            router.delete(`/super-admin/tenants/${tenant.id}`, {
                preserveScroll: true,
            });
        }
    };

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
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Gerenciar Tenants</h2>}
        >
            <Head title="Tenants" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {/* Header */}
                            <div className="sm:flex sm:items-center sm:justify-between mb-6">
                                <div>
                                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                                        Tenants
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Gerencie todos os tenants do sistema
                                    </p>
                                </div>
                                <div className="mt-4 sm:mt-0">
                                    <Link
                                        href="/super-admin/tenants/create"
                                        className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        <PlusIcon className="-ml-1 mr-2 h-5 w-5" />
                                        Novo Tenant
                                    </Link>
                                </div>
                            </div>

                            {/* Filters */}
                            <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <form onSubmit={handleSearch} className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <input
                                        type="text"
                                        placeholder="Buscar por nome ou email..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </form>

                                <select
                                    value={status}
                                    onChange={(e) => handleStatusChange(e.target.value)}
                                    className="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                >
                                    <option value="">Todos os status</option>
                                    <option value="active">Ativo</option>
                                    <option value="paused">Pausado</option>
                                    <option value="inactive">Inativo</option>
                                </select>
                            </div>

                            {/* Table */}
                            <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                <table className="min-w-full divide-y divide-gray-300">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Tenant
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Usuários
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Criado em
                                            </th>
                                            <th className="relative px-6 py-3">
                                                <span className="sr-only">Ações</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {tenants.data.map((tenant) => (
                                            <tr key={tenant.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <div className="flex-shrink-0 h-10 w-10">
                                                            <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                                <span className="text-sm font-medium text-gray-700">
                                                                    {tenant.name.charAt(0).toUpperCase()}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div className="ml-4">
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {tenant.name}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                {tenant.email}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {getStatusBadge(tenant.status)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {tenant.users_count || 0}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {new Date(tenant.created_at).toLocaleDateString('pt-BR')}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex items-center justify-end space-x-2">
                                                        <Link
                                                            href={`/super-admin/tenants/${tenant.id}`}
                                                            className="text-indigo-600 hover:text-indigo-900"
                                                            title="Ver detalhes"
                                                        >
                                                            <EyeIcon className="h-5 w-5" />
                                                        </Link>
                                                        <Link
                                                            href={`/super-admin/tenants/${tenant.id}/edit`}
                                                            className="text-yellow-600 hover:text-yellow-900"
                                                            title="Editar"
                                                        >
                                                            <PencilIcon className="h-5 w-5" />
                                                        </Link>
                                                        <button
                                                            onClick={() => toggleTenantStatus(tenant)}
                                                            className={`${
                                                                tenant.status === 'active'
                                                                    ? 'text-yellow-600 hover:text-yellow-900'
                                                                    : 'text-green-600 hover:text-green-900'
                                                            }`}
                                                            title={tenant.status === 'active' ? 'Pausar' : 'Ativar'}
                                                        >
                                                            {tenant.status === 'active' ? (
                                                                <PauseIcon className="h-5 w-5" />
                                                            ) : (
                                                                <PlayIcon className="h-5 w-5" />
                                                            )}
                                                        </button>
                                                        <button
                                                            onClick={() => deleteTenant(tenant)}
                                                            className="text-red-600 hover:text-red-900"
                                                            title="Excluir"
                                                        >
                                                            <TrashIcon className="h-5 w-5" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {tenants.links && (
                                <div className="mt-6 flex items-center justify-between">
                                    <div className="flex-1 flex justify-between sm:hidden">
                                        {tenants.prev_page_url && (
                                            <Link
                                                href={tenants.prev_page_url}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Anterior
                                            </Link>
                                        )}
                                        {tenants.next_page_url && (
                                            <Link
                                                href={tenants.next_page_url}
                                                className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Próximo
                                            </Link>
                                        )}
                                    </div>
                                    <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-gray-700">
                                                Mostrando <span className="font-medium">{tenants.from}</span> até{' '}
                                                <span className="font-medium">{tenants.to}</span> de{' '}
                                                <span className="font-medium">{tenants.total}</span> resultados
                                            </p>
                                        </div>
                                        <div>
                                            <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                                {tenants.links.map((link, index) => (
                                                    <Link
                                                        key={index}
                                                        href={link.url || '#'}
                                                        className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                            link.active
                                                                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                                : link.url
                                                                ? 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                                : 'bg-white border-gray-300 text-gray-300 cursor-not-allowed'
                                                        } ${
                                                            index === 0 ? 'rounded-l-md' : ''
                                                        } ${
                                                            index === tenants.links.length - 1 ? 'rounded-r-md' : ''
                                                        }`}
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                ))}
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </SuperAdminLayout>
    );
}