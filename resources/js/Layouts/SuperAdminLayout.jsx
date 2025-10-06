import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    Bars3Icon,
    XMarkIcon,
    HomeIcon,
    BuildingOfficeIcon,
    UserGroupIcon,
    Cog6ToothIcon,
    ArrowRightOnRectangleIcon,
} from '@heroicons/react/24/outline';

const navigation = [
    { name: 'Dashboard', href: '/super-admin', icon: HomeIcon },
    { name: 'Tenants', href: '/super-admin/tenants', icon: BuildingOfficeIcon },
    { name: 'Usuários', href: '/super-admin/users', icon: UserGroupIcon },
    { name: 'Configurações', href: '/super-admin/settings', icon: Cog6ToothIcon },
];

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export default function SuperAdminLayout({ user, header, children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { url } = usePage();

    return (
        <div className="h-full">
            {/* Mobile sidebar */}
            <div className={`relative z-50 lg:hidden ${sidebarOpen ? '' : 'hidden'}`}>
                <div className="fixed inset-0 bg-gray-900/80" onClick={() => setSidebarOpen(false)} />
                
                <div className="fixed inset-0 flex">
                    <div className="relative mr-16 flex w-full max-w-xs flex-1">
                        <div className="absolute left-full top-0 flex w-16 justify-center pt-5">
                            <button
                                type="button"
                                className="-m-2.5 p-2.5"
                                onClick={() => setSidebarOpen(false)}
                            >
                                <span className="sr-only">Close sidebar</span>
                                <XMarkIcon className="h-6 w-6 text-white" aria-hidden="true" />
                            </button>
                        </div>
                        
                        <div className="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-2">
                            <div className="flex h-16 shrink-0 items-center">
                                <h1 className="text-xl font-bold text-gray-900">Super Admin</h1>
                            </div>
                            <nav className="flex flex-1 flex-col">
                                <ul className="flex flex-1 flex-col gap-y-7">
                                    <li>
                                        <ul className="-mx-2 space-y-1">
                                            {navigation.map((item) => (
                                                <li key={item.name}>
                                                    <Link
                                                        href={item.href}
                                                        className={classNames(
                                                            url.startsWith(item.href)
                                                                ? 'bg-gray-50 text-indigo-600'
                                                                : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50',
                                                            'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold'
                                                        )}
                                                    >
                                                        <item.icon
                                                            className={classNames(
                                                                url.startsWith(item.href)
                                                                    ? 'text-indigo-600'
                                                                    : 'text-gray-400 group-hover:text-indigo-600',
                                                                'h-6 w-6 shrink-0'
                                                            )}
                                                            aria-hidden="true"
                                                        />
                                                        {item.name}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            {/* Static sidebar for desktop */}
            <div className="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
                <div className="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6">
                    <div className="flex h-16 shrink-0 items-center">
                        <h1 className="text-xl font-bold text-gray-900">Super Admin</h1>
                    </div>
                    <nav className="flex flex-1 flex-col">
                        <ul className="flex flex-1 flex-col gap-y-7">
                            <li>
                                <ul className="-mx-2 space-y-1">
                                    {navigation.map((item) => (
                                        <li key={item.name}>
                                            <Link
                                                href={item.href}
                                                className={classNames(
                                                    url.startsWith(item.href)
                                                        ? 'bg-gray-50 text-indigo-600'
                                                        : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50',
                                                    'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold'
                                                )}
                                            >
                                                <item.icon
                                                    className={classNames(
                                                        url.startsWith(item.href)
                                                            ? 'text-indigo-600'
                                                            : 'text-gray-400 group-hover:text-indigo-600',
                                                        'h-6 w-6 shrink-0'
                                                    )}
                                                    aria-hidden="true"
                                                />
                                                {item.name}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </li>
                            <li className="-mx-6 mt-auto">
                                <div className="flex items-center gap-x-4 px-6 py-3 text-sm font-semibold leading-6 text-gray-900">
                                    <img
                                        className="h-8 w-8 rounded-full bg-gray-50"
                                        src={user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&color=7F9CF5&background=EBF4FF`}
                                        alt={user.name}
                                    />
                                    <span className="sr-only">Your profile</span>
                                    <span aria-hidden="true">{user.name}</span>
                                </div>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="flex w-full items-center gap-x-3 px-6 py-3 text-sm font-semibold leading-6 text-gray-700 hover:bg-gray-50"
                                >
                                    <ArrowRightOnRectangleIcon className="h-6 w-6 text-gray-400" />
                                    Sair
                                </Link>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <div className="sticky top-0 z-40 flex items-center gap-x-6 bg-white px-4 py-4 shadow-sm sm:px-6 lg:hidden">
                <button
                    type="button"
                    className="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                    onClick={() => setSidebarOpen(true)}
                >
                    <span className="sr-only">Open sidebar</span>
                    <Bars3Icon className="h-6 w-6" aria-hidden="true" />
                </button>
                <div className="flex-1 text-sm font-semibold leading-6 text-gray-900">Super Admin</div>
                <Link href="/logout" method="post" as="button">
                    <span className="sr-only">Your profile</span>
                    <img
                        className="h-8 w-8 rounded-full bg-gray-50"
                        src={user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&color=7F9CF5&background=EBF4FF`}
                        alt={user.name}
                    />
                </Link>
            </div>

            <main className="py-10 lg:pl-72">
                <div className="px-4 sm:px-6 lg:px-8">
                    {header && (
                        <header className="mb-8">
                            <div className="mx-auto max-w-7xl">
                                {header}
                            </div>
                        </header>
                    )}
                    {children}
                </div>
            </main>
        </div>
    );
}