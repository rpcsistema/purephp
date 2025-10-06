import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    FunnelIcon,
    UserIcon,
    BuildingOfficeIcon,
} from '@heroicons/react/24/outline';

export default function Index({ auth, users, filters, stats }) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [roleFilter, setRoleFilter] = useState(filters.role || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/super-admin/users', {
            search,
            status: statusFilter,
            role: roleFilter,
        });
    };

    const handleDelete = (user) => {
        if (confirm(`Tem certeza que deseja excluir o usuário ${user.name}?`)) {
            router.delete(`/super-admin/users/${user.id}`);
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-red-100 text-red-800',
            pending: 'bg-yellow-100 text-yellow-800',
        };
        
        const labels = {
            active: 'Ativo',
            inactive: 'Inativo',
            pending: 'Pendente',
        };

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badges[status]}`}>
                {labels[status]}
            </span>
        );
    };

    const getRoleBadge = (role) => {
        const badges = {
            super_admin: 'bg-purple-100 text-purple-800',
            admin: 'bg-blue-100 text-blue-800',
            user: 'bg-gray-100 text-gray-800',
        };
        
        const labels = {
            super_admin: 'Super Admin',
            admin: 'Admin',
            user: 'Usuário',
        };

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badges[role]}`}>
                {labels[role]}
            </span>
        );
    };

    return (
        <SuperAdminLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Gerenciamento de Usuários
                    </h2>
                    <Link
                        href="/super-admin/users/create"
                        className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                        <PlusIcon className="-ml-1 mr-2 h-5 w-5" />
                        Novo Usuário
                    </Link>
                </div>
            }
        >
            <Head title="Usuários" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Estatísticas */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <UserIcon className="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total de Usuários
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats?.total || 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="w-6 h-6 bg-green-500 rounded-full"></div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Usuários Ativos
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats?.active || 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <BuildingOfficeIcon className="h-6 w-6 text-blue-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Administradores
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats?.admins || 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="w-6 h-6 bg-yellow-500 rounded-full"></div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Pendentes
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats?.pending || 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white shadow overflow-hidden sm:rounded-md">
                        {/* Filtros */}
                        <div className="bg-gray-50 px-4 py-5 border-b border-gray-200 sm:px-6">
                            <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-4">
                                <div className="flex-1">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            type="text"
                                            placeholder="Buscar por nome ou email..."
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                        />
                                    </div>
                                </div>
                                
                                <div className="flex gap-2">
                                    <select
                                        value={statusFilter}
                                        onChange={(e) => setStatusFilter(e.target.value)}
                                        className="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                    >
                                        <option value="">Todos os Status</option>
                                        <option value="active">Ativo</option>
                                        <option value="inactive">Inativo</option>
                                        <option value="pending">Pendente</option>
                                    </select>

                                    <select
                                        value={roleFilter}
                                        onChange={(e) => setRoleFilter(e.target.value)}
                                        className="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                    >
                                        <option value="">Todos os Papéis</option>
                                        <option value="super_admin">Super Admin</option>
                                        <option value="admin">Admin</option>
                                        <option value="user">Usuário</option>
                                    </select>

                                    <button
                                        type="submit"
                                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        <FunnelIcon className="h-4 w-4 mr-2" />
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        {/* Lista de Usuários */}
                        <ul className="divide-y divide-gray-200">
                            {users.data.map((user) => (
                                <li key={user.id}>
                                    <div className="px-4 py-4 flex items-center justify-between">
                                        <div className="flex items-center">
                                            <div className="flex-shrink-0 h-10 w-10">
                                                <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <span className="text-sm font-medium text-gray-700">
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="ml-4">
                                                <div className="flex items-center space-x-2">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {user.name}
                                                    </div>
                                                    {getRoleBadge(user.role)}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {user.email}
                                                </div>
                                                <div className="flex items-center space-x-4 mt-1">
                                                    <div className="text-xs text-gray-500">
                                                        Tenant: {user.tenant?.name || 'N/A'}
                                                    </div>
                                                    {getStatusBadge(user.status)}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <Link
                                                href={`/super-admin/users/${user.id}`}
                                                className="text-indigo-600 hover:text-indigo-900"
                                            >
                                                <EyeIcon className="h-5 w-5" />
                                            </Link>
                                            <Link
                                                href={`/super-admin/users/${user.id}/edit`}
                                                className="text-gray-600 hover:text-gray-900"
                                            >
                                                <PencilIcon className="h-5 w-5" />
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(user)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                <TrashIcon className="h-5 w-5" />
                                            </button>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>

                        {/* Paginação */}
                        {users.links && (
                            <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div className="flex-1 flex justify-between sm:hidden">
                                    {users.prev_page_url && (
                                        <Link
                                            href={users.prev_page_url}
                                            className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Anterior
                                        </Link>
                                    )}
                                    {users.next_page_url && (
                                        <Link
                                            href={users.next_page_url}
                                            className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Próximo
                                        </Link>
                                    )}
                                </div>
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Mostrando{' '}
                                            <span className="font-medium">{users.from}</span>
                                            {' '}até{' '}
                                            <span className="font-medium">{users.to}</span>
                                            {' '}de{' '}
                                            <span className="font-medium">{users.total}</span>
                                            {' '}resultados
                                        </p>
                                    </div>
                                    <div>
                                        <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            {users.links.map((link, index) => (
                                                <Link
                                                    key={index}
                                                    href={link.url || '#'}
                                                    className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                        link.active
                                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    } ${
                                                        index === 0 ? 'rounded-l-md' : ''
                                                    } ${
                                                        index === users.links.length - 1 ? 'rounded-r-md' : ''
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
        </SuperAdminLayout>
    );
}