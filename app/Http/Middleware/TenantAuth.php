<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Super admins can access any tenant
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Regular users must belong to the current tenant
        $currentTenant = tenant();
        
        if (!$currentTenant) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Acesso negado: Tenant não encontrado.');
        }

        // Check if user belongs to current tenant (this would need to be implemented based on your tenant user relationship)
        // For now, we'll allow access if user is authenticated
        
        return $next($request);
    }
}