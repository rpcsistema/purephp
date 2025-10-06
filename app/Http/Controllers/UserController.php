<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('tenant');

        // Filtros
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        // Estatísticas
        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'admins' => User::whereIn('role', ['admin', 'super_admin'])->count(),
            'pending' => User::where('status', 'pending')->count(),
        ];

        return Inertia::render('SuperAdmin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'role', 'tenant_id']),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $tenants = Tenant::where('status', 'active')->get();

        return Inertia::render('SuperAdmin/Users/Create', [
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:super_admin,admin,user',
            'status' => 'required|in:active,inactive,pending',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
            'tenant_id' => $request->tenant_id,
            'email_verified_at' => $request->status === 'active' ? now() : null,
        ]);

        return redirect()->route('super-admin.users.index')
            ->with('success', 'Usuário criado com sucesso!');
    }

    public function show(User $user)
    {
        $user->load('tenant');

        // Estatísticas do usuário
        $stats = [];
        
        if ($user->tenant_id) {
            // Se for um usuário de tenant, buscar estatísticas específicas
            $stats = [
                'transactions_count' => 0, // Implementar quando tiver transações
                'last_login' => $user->last_login_at,
                'created_at' => $user->created_at,
            ];
        }

        return Inertia::render('SuperAdmin/Users/Show', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    public function edit(User $user)
    {
        $user->load('tenant');
        $tenants = Tenant::where('status', 'active')->get();

        return Inertia::render('SuperAdmin/Users/Edit', [
            'user' => $user,
            'tenants' => $tenants,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:super_admin,admin,user',
            'status' => 'required|in:active,inactive,pending',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status,
            'tenant_id' => $request->tenant_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Se o status mudou para ativo e o email não foi verificado
        if ($request->status === 'active' && !$user->email_verified_at) {
            $data['email_verified_at'] = now();
        }

        $user->update($data);

        return redirect()->route('super-admin.users.show', $user)
            ->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(User $user)
    {
        // Não permitir deletar super admins
        if ($user->role === 'super_admin') {
            return back()->with('error', 'Não é possível deletar um Super Administrador.');
        }

        // Não permitir deletar o próprio usuário
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode deletar sua própria conta.');
        }

        $user->delete();

        return redirect()->route('super-admin.users.index')
            ->with('success', 'Usuário deletado com sucesso!');
    }

    public function toggleStatus(User $user)
    {
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        
        $user->update([
            'status' => $newStatus,
            'email_verified_at' => $newStatus === 'active' ? now() : $user->email_verified_at,
        ]);

        return back()->with('success', 'Status do usuário atualizado com sucesso!');
    }

    public function impersonate(User $user)
    {
        // Verificar se o usuário atual é super admin
        if (!auth()->user()->is_super_admin) {
            abort(403, 'Acesso negado.');
        }

        // Não permitir impersonar outros super admins
        if ($user->role === 'super_admin') {
            return back()->with('error', 'Não é possível impersonar outro Super Administrador.');
        }

        // Salvar o ID do super admin na sessão
        session(['impersonating' => auth()->id()]);
        
        // Fazer login como o usuário
        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('success', "Você está agora logado como {$user->name}");
    }

    public function stopImpersonating()
    {
        if (!session('impersonating')) {
            return redirect()->route('dashboard');
        }

        $superAdminId = session('impersonating');
        session()->forget('impersonating');

        $superAdmin = User::find($superAdminId);
        auth()->login($superAdmin);

        return redirect()->route('super-admin.dashboard')
            ->with('success', 'Você voltou para sua conta de Super Administrador.');
    }
}