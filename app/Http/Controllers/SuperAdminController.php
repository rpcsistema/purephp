<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'paused_tenants' => Tenant::where('status', 'paused')->count(),
            'suspended_tenants' => Tenant::where('status', 'suspended')->count(),
            'total_users' => User::count(),
        ];

        $recentTenants = Tenant::latest()->take(5)->get();
        $recentUsers = User::with('tenant')->latest()->take(5)->get();

        // Tenant growth by month
        $tenantGrowth = Tenant::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get()
            ->reverse()
            ->values();

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => $stats,
            'recent_tenants' => $recentTenants,
            'recent_users' => $recentUsers,
            'tenant_growth' => $tenantGrowth,
        ]);
    }

    public function index(Request $request)
    {
        $query = Tenant::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        $tenants = $query->withCount('users')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status', 'plan']),
        ]);
    }

    public function create()
    {
        return Inertia::render('SuperAdmin/Tenants/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tenants',
            'plan' => 'required|in:basic,premium,enterprise',
            'status' => 'required|in:active,paused,suspended',
            'settings' => 'nullable|array',
            'white_label_settings' => 'nullable|array',
            
            // Admin user data
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|string|email|max:255',
            'admin_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Create tenant
        $tenant = Tenant::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'plan' => $validated['plan'],
            'status' => $validated['status'],
            'settings' => $validated['settings'] ?? [],
            'white_label_settings' => $validated['white_label_settings'] ?? [],
        ]);

        // Create admin user for the tenant
        tenancy()->initialize($tenant);
        
        $adminUser = User::create([
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
            'email_verified_at' => now(),
        ]);

        // Assign admin role
        $adminUser->assignRole('admin');

        tenancy()->end();

        return redirect()->route('super-admin.tenants.index')
            ->with('success', 'Tenant criado com sucesso!');
    }

    public function show(Tenant $tenant)
    {
        tenancy()->initialize($tenant);
        
        $users = User::with('roles')->paginate(10);
        $userStats = [
            'total' => User::count(),
            'admins' => User::role('admin')->count(),
            'managers' => User::role('manager')->count(),
            'users' => User::role('user')->count(),
        ];

        tenancy()->end();

        return Inertia::render('SuperAdmin/Tenants/Show', [
            'tenant' => $tenant,
            'users' => $users,
            'user_stats' => $userStats,
        ]);
    }

    public function edit(Tenant $tenant)
    {
        return Inertia::render('SuperAdmin/Tenants/Edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tenants,email,' . $tenant->id,
            'plan' => 'required|in:basic,premium,enterprise',
            'status' => 'required|in:active,paused,suspended',
            'settings' => 'nullable|array',
            'white_label_settings' => 'nullable|array',
        ]);

        $tenant->update($validated);

        return redirect()->route('super-admin.tenants.index')
            ->with('success', 'Tenant atualizado com sucesso!');
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return redirect()->route('super-admin.tenants.index')
            ->with('success', 'Tenant excluído com sucesso!');
    }

    public function toggleStatus(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,paused,suspended',
        ]);

        $tenant->update(['status' => $validated['status']]);

        return back()->with('success', 'Status do tenant atualizado com sucesso!');
    }
}