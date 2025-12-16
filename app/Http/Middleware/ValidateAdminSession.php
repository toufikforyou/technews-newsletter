<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidateAdminSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if admin is authenticated
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            
            // Get remember token from cookie (Laravel's default cookie name format)
            $cookieName = 'remember_admin_' . sha1(get_class(Auth::guard('admin')));
            $rememberCookie = $request->cookies->get($cookieName);
            
            // If remember cookie exists
            if ($rememberCookie) {
                // Extract token from cookie (format: id|token|hash)
                $segments = explode('|', $rememberCookie);
                $tokenFromCookie = $segments[1] ?? null;
                
                // Compare with database token
                if ($tokenFromCookie && $admin->remember_token && $tokenFromCookie !== $admin->remember_token) {
                    // Token mismatch - session was invalidated from another device
                    Auth::guard('admin')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    return redirect()->route('admin.login')
                        ->with('error', 'Your session has been terminated. Please login again.');
                }
            }
        }
        
        return $next($request);
    }
}
