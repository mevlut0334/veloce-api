<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sadece authenticate edilmiş kullanıcılar için çalışır
        if ($request->user()) {
            $user = $request->user();

            // Kullanıcının aktif aboneliğini kontrol et
            $activeSubscription = $user->userSubscriptions()
                ->where('status', 'active')
                ->first();

            if ($activeSubscription) {
                // Abonelik süresi dolmuş mu kontrol et
                if ($activeSubscription->expires_at <= now()) {
                    // Durumu expired olarak güncelle
                    $activeSubscription->update(['status' => 'expired']);
                }
            }

            // Son aktivite zamanını güncelle
            $user->update(['last_activity_at' => now()]);
        }

        return $next($request);
    }
}
