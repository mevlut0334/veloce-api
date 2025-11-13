<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kullanıcı giriş yapmış mı kontrol et
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Lütfen giriş yapın.');
        }

        // Kullanıcı admin mi kontrol et
        // NOT: User modelinizde is_admin veya role kolonu olmalı
        if (!auth()->user()->is_admin) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        return $next($request);
    }
}
