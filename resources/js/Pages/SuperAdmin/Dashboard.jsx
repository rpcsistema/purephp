import React from 'react';
import { Head } from '@inertiajs/react';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout';
import {
    UserGroupIcon,
    BuildingOfficeIcon,
    ChartBarIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';

export default function Dashboard({ auth, stats, recent_tenants, recent_users, tenant_growth }) {
    const statCards = [
        {
            name: 'Total de Tenants',
            value: stats.total_tenants,
            icon: BuildingOfficeIcon,
            color: 'bg-blue-500',
        },
        {
            name: 'Tenants Ativos',
            value: stats.active_tenants,
            icon: ChartBarIcon,
            color: 'bg-green-500',
        },
        {
            name: 'Tenants Pausados',
            value: stats.paused_tenants,
            icon: ExclamationTriangleIcon,
            color: 'bg-yellow-500',
        },
        {
            name: 'Total de Usuários',
            value: stats.total_users,
            icon: UserGroupIcon,
            color: 'bg-purple-500',
        },
    ];

    return (
        <SuperAdminLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Super Admin Dashboard</h2>}
        >
            <Head title="Super Admin Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Welcome Message */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium">
                                Bem-vindo ao Painel de Super Administrador, {auth.user.name}!
                            </h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Gerencie todos os tenants e usuários do sistema.
                            </p>
                        </div>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                        {statCards.map((item) => (
                            <div
                                key={item.name}
                                className="relative bg-white pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden"
                            >
                                <dt>
                                    <div className={`absolute ${item.color} rounded-md p-3`}>
                                        <item.icon className="h-6 w-6 text-white" aria-hidden="true" />
                                    </div>
                                    <p className="ml-16 text-sm font-medium text-gray-500 truncate">
                                        {item.name}
                                    </p>
                                </dt>
                                <dd className="ml-16 pb-6 flex items-baseline sm:pb-7">
                                    <p className="text-2xl font-semibold text-gray-900">
                                        {item.value}
                                    </p>
                                </dd>
                            </div>
                        ))}
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Recent Tenants */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Tenants Recentes
                                </h3>
                                <div className="flow-root">
                                    <ul className="-my-5 divide-y divide-gray-200">
                                        {recent_tenants.map((tenant) => (
                                            <li key={tenant.id} className="py-4">
                                                <div className="flex items-center space-x-4">
                                                    <div className="flex-shrink-0">
                                                        <div className="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                                            <BuildingOfficeIcon className="h-5 w-5 text-gray-500" />
                                                        </div>
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-medium text-gray-900 truncate">
                                                            {tenant.name}
                                                        </p>
                                                        <p className="text-sm text-gray-500 truncate">
                                                            {tenant.email}
                                                        </p>
                                                    </div>
                                                    <div className="flex-shrink-0">
                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                            tenant.status === 'active' 
                                                                ? 'bg-green-100 text-green-800'
                                                                : tenant.status === 'paused'
                                                                ? 'bg-yellow-100 text-yellow-800'
                                                                : 'bg-red-100 text-red-800'
                                                        }`}>
                                                            {tenant.status}
                                                        </span>
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                                <div className="mt-6">
                                    <a
                                        href="/super-admin/tenants"
                                        className="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Ver todos os tenants
                                    </a>
                                </div>
                            </div>
                        </div>

                        {/* Recent Users */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Usuários Recentes
                                </h3>
                                <div className="flow-root">
                                    <ul className="-my-5 divide-y divide-gray-200">
                                        {recent_users.map((user) => (
                                            <li key={user.id} className="py-4">
                                                <div className="flex items-center space-x-4">
                                                    <div className="flex-shrink-0">
                                                        <img
                                                            className="h-8 w-8 rounded-full"
                                                            src={user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&color=7F9CF5&background=EBF4FF`}
                                                            alt={user.name}
                                                        />
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-medium text-gray-900 truncate">
                                                            {user.name}
                                                        </p>
                                                        <p className="text-sm text-gray-500 truncate">
                                                            {user.email}
                                                        </p>
                                                    </div>
                                                    <div className="flex-shrink-0">
                                                        <p className="text-xs text-gray-500">
                                                            {user.tenant?.name}
                                                        </p>
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Tenant Growth Chart */}
                    {tenant_growth && tenant_growth.length > 0 && (
                        <div className="mt-8 bg-white shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Crescimento de Tenants (Últimos 12 meses)
                                </h3>
                                <div className="mt-4">
                                    <div className="flex items-end space-x-2 h-32">
                                        {tenant_growth.map((item, index) => (
                                            <div key={index} className="flex-1 flex flex-col items-center">
                                                <div 
                                                    className="bg-blue-500 w-full rounded-t"
                                                    style={{ 
                                                        height: `${Math.max((item.count / Math.max(...tenant_growth.map(g => g.count))) * 100, 5)}%` 
                                                    }}
                                                ></div>
                                                <div className="text-xs text-gray-500 mt-1">
                                                    {item.month}/{item.year}
                                                </div>
                                                <div className="text-xs font-medium text-gray-900">
                                                    {item.count}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </SuperAdminLayout>
    );
}