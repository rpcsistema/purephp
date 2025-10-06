import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    Bars3Icon,
    XMarkIcon,
    HomeIcon,
    ChartBarIcon,
    CurrencyDollarIcon,
    DocumentReportIcon,
    UserGroupIcon,
    Cog6ToothIcon,
    ArrowRightOnRectangleIcon,
} from '@heroicons/react/24/outline';

const navigation = [
    { name: 'Dashboard', href: '/dashboard', icon: HomeIcon },
    { name: 'Financeiro', href: '/financial', icon: CurrencyDollarIcon },
    { name: 'Transações', href: '/financial/transactions', icon: ChartBarIcon },
    { name: 'Categorias', href: '/financial/categories', icon: DocumentReportIcon },
    { name: 'Orçamentos', href: '/financial/budgets', icon: DocumentReportIcon },
    { name: 'Relatórios', href: '/financial/reports', icon: ChartBarIcon },
];

const adminNavigation = [
    { name: 'Usuários', href: '/users', icon: UserGroupIcon },
    { name: 'Configurações', href: '/settings', icon: Cog6ToothIcon },
];

export default function AuthenticatedLayout({ user, tenant, header, children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { url } = usePage();

    const isActive = (href) => {
        if (href === '/dashboard') {
            return url === href;
        }
        return url.startsWith(href);
    };

    const canAccess = (permission) => {
        return user.permissions?.includes(permission) || user.is_super_admin;
    };

    return (
        <div className="min-h-screen bg-gray-100">
            {/* Mobile sidebar */}
            <div className={`fixed inset-0 flex z-40 md:hidden ${sidebarOpen ? '' : 'hidden'}`}>
                <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={() => setSidebarOpen(false)} />
                <div className="relative flex-1 flex flex-col max-w-xs w-full bg-gray-800">
                    <div className="absolute top-0 right-0 -mr-12 pt-2">
                        <button
                            type="button"
                            className="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                            onClick={() => setSidebarOpen(false)}
                        >
                            <XMarkIcon className="h-6 w-6 text-white" />
                        </button>
                    </div>
                    <div className="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                        <div className="flex-shrink-0 flex items-center px-4">
                            <h1 className="text-white text-lg font-semibold">
                                {tenant?.white_label_settings?.app_name || 'SaaS WL'}
                            </h1>
                        </div>
                        <nav className="mt-5 px-2 space-y-1">
                            {navigation.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`group flex items-center px-2 py-2 text-base font-medium rounded-md ${
                                        isActive(item.href)
                                            ? 'bg-gray-900 text-white'
                                            : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                                    }`}
                                >
                                    <item.icon className="mr-4 h-6 w-6" />
                                    {item.name}
                                </Link>
                            ))}
                            
                            {/* Admin Navigation */}
                            {(user.is_tenant_admin || user.is_super_admin) && (
                                <>
                                    <div className="border-t border-gray-700 mt-4 pt-4">
                                        <p className="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                            Administração
                                        </p>
                                    </div>
                                    {adminNavigation.map((item) => (
                                        canAccess(item.permission || 'admin') && (
                                            <Link
                                                key={item.name}
                                                href={item.href}
                                                className={`group flex items-center px-2 py-2 text-base font-medium rounded-md ${
                                                    isActive(item.href)
                                                        ? 'bg-gray-900 text-white'
                                                        : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                                                }`}
                                            >
                                                <item.icon className="mr-4 h-6 w-6" />
                                                {item.name}
                                            </Link>
                                        )
                                    ))}
                                </>
                            )}
                        </nav>
                    </div>
                </div>
            </div>

            {/* Static sidebar for desktop */}
            <div className="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
                <div className="flex-1 flex flex-col min-h-0 bg-gray-800">
                    <div className="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                        <div className="flex items-center flex-shrink-0 px-4">
                            <h1 className="text-white text-lg font-semibold">
                                {tenant?.white_label_settings?.app_name || 'SaaS WL'}
                            </h1>
                        </div>
                        <nav className="mt-5 flex-1 px-2 space-y-1">
                            {navigation.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`group flex items-center px-2 py-2 text-sm font-medium rounded-md ${
                                        isActive(item.href)
                                            ? 'bg-gray-900 text-white'
                                            : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                                    }`}
                                >
                                    <item.icon className="mr-3 h-6 w-6" />
                                    {item.name}
                                </Link>
                            ))}
                            
                            {/* Admin Navigation */}
                            {(user.is_tenant_admin || user.is_super_admin) && (
                                <>
                                    <div className="border-t border-gray-700 mt-4 pt-4">
                                        <p className="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                            Administração
                                        </p>
                                    </div>
                                    {adminNavigation.map((item) => (
                                        canAccess(item.permission || 'admin') && (
                                            <Link
                                                key={item.name}
                                                href={item.href}
                                                className={`group flex items-center px-2 py-2 text-sm font-medium rounded-md ${
                                                    isActive(item.href)
                                                        ? 'bg-gray-900 text-white'
                                                        : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                                                }`}
                                            >
                                                <item.icon className="mr-3 h-6 w-6" />
                                                {item.name}
                                            </Link>
                                        )
                                    ))}
                                </>
                            )}
                        </nav>
                    </div>
                    
                    {/* User menu */}
                    <div className="flex-shrink-0 flex bg-gray-700 p-4">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <img
                                    className="h-8 w-8 rounded-full"
                                    src={user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&color=7F9CF5&background=EBF4FF`}
                                    alt={user.name}
                                />
                            </div>
                            <div className="ml-3">
                                <p className="text-sm font-medium text-white">{user.name}</p>
                                <p className="text-xs font-medium text-gray-300">{user.email}</p>
                            </div>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                className="ml-auto flex-shrink-0 p-1 text-gray-400 hover:text-white"
                            >
                                <ArrowRightOnRectangleIcon className="h-6 w-6" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main content */}
            <div className="md:pl-64 flex flex-col flex-1">
                <div className="sticky top-0 z-10 md:hidden pl-1 pt-1 sm:pl-3 sm:pt-3 bg-gray-100">
                    <button
                        type="button"
                        className="-ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                        onClick={() => setSidebarOpen(true)}
                    >
                        <Bars3Icon className="h-6 w-6" />
                    </button>
                </div>
                
                <main className="flex-1">
                    {header && (
                        <div className="bg-white shadow">
                            <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                {header}
                            </div>
                        </div>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}