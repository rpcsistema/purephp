import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    ChartBarIcon, 
    CurrencyDollarIcon, 
    UserGroupIcon, 
    DocumentReportIcon 
} from '@heroicons/react/24/outline';

export default function Dashboard({ auth, tenant }) {
    const stats = [
        {
            name: 'Receitas do Mês',
            value: 'R$ 12.450,00',
            change: '+12%',
            changeType: 'increase',
            icon: CurrencyDollarIcon,
        },
        {
            name: 'Despesas do Mês',
            value: 'R$ 8.230,00',
            change: '-3%',
            changeType: 'decrease',
            icon: ChartBarIcon,
        },
        {
            name: 'Lucro Líquido',
            value: 'R$ 4.220,00',
            change: '+18%',
            changeType: 'increase',
            icon: DocumentReportIcon,
        },
        {
            name: 'Usuários Ativos',
            value: '24',
            change: '+2',
            changeType: 'increase',
            icon: UserGroupIcon,
        },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            tenant={tenant}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Welcome Message */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium">
                                Bem-vindo, {auth.user.name}!
                            </h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Aqui está um resumo das suas atividades financeiras.
                            </p>
                        </div>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                        {stats.map((item) => (
                            <div
                                key={item.name}
                                className="relative bg-white pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden"
                            >
                                <dt>
                                    <div className="absolute bg-indigo-500 rounded-md p-3">
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
                                    <p
                                        className={`ml-2 flex items-baseline text-sm font-semibold ${
                                            item.changeType === 'increase'
                                                ? 'text-green-600'
                                                : 'text-red-600'
                                        }`}
                                    >
                                        {item.change}
                                    </p>
                                </dd>
                            </div>
                        ))}
                    </div>

                    {/* Quick Actions */}
                    <div className="bg-white shadow rounded-lg">
                        <div className="px-4 py-5 sm:p-6">
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Ações Rápidas
                            </h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <a
                                    href="/financial/transactions/create"
                                    className="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg border border-gray-200 hover:border-gray-300"
                                >
                                    <div>
                                        <span className="rounded-lg inline-flex p-3 bg-green-50 text-green-700 ring-4 ring-white">
                                            <CurrencyDollarIcon className="h-6 w-6" aria-hidden="true" />
                                        </span>
                                    </div>
                                    <div className="mt-8">
                                        <h3 className="text-lg font-medium">
                                            <span className="absolute inset-0" aria-hidden="true" />
                                            Nova Transação
                                        </h3>
                                        <p className="mt-2 text-sm text-gray-500">
                                            Registre uma nova receita ou despesa
                                        </p>
                                    </div>
                                </a>

                                <a
                                    href="/financial/categories"
                                    className="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg border border-gray-200 hover:border-gray-300"
                                >
                                    <div>
                                        <span className="rounded-lg inline-flex p-3 bg-blue-50 text-blue-700 ring-4 ring-white">
                                            <ChartBarIcon className="h-6 w-6" aria-hidden="true" />
                                        </span>
                                    </div>
                                    <div className="mt-8">
                                        <h3 className="text-lg font-medium">
                                            <span className="absolute inset-0" aria-hidden="true" />
                                            Categorias
                                        </h3>
                                        <p className="mt-2 text-sm text-gray-500">
                                            Gerencie suas categorias financeiras
                                        </p>
                                    </div>
                                </a>

                                <a
                                    href="/financial/budgets"
                                    className="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg border border-gray-200 hover:border-gray-300"
                                >
                                    <div>
                                        <span className="rounded-lg inline-flex p-3 bg-yellow-50 text-yellow-700 ring-4 ring-white">
                                            <DocumentReportIcon className="h-6 w-6" aria-hidden="true" />
                                        </span>
                                    </div>
                                    <div className="mt-8">
                                        <h3 className="text-lg font-medium">
                                            <span className="absolute inset-0" aria-hidden="true" />
                                            Orçamentos
                                        </h3>
                                        <p className="mt-2 text-sm text-gray-500">
                                            Controle seus orçamentos mensais
                                        </p>
                                    </div>
                                </a>

                                <a
                                    href="/financial/reports"
                                    className="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg border border-gray-200 hover:border-gray-300"
                                >
                                    <div>
                                        <span className="rounded-lg inline-flex p-3 bg-purple-50 text-purple-700 ring-4 ring-white">
                                            <DocumentReportIcon className="h-6 w-6" aria-hidden="true" />
                                        </span>
                                    </div>
                                    <div className="mt-8">
                                        <h3 className="text-lg font-medium">
                                            <span className="absolute inset-0" aria-hidden="true" />
                                            Relatórios
                                        </h3>
                                        <p className="mt-2 text-sm text-gray-500">
                                            Visualize relatórios detalhados
                                        </p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}