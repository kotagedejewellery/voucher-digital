<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class PrivacyHeaders {
    public function handle(Request $request, Closure $next) {
        $response=$next($request);
        $response->headers->set('X-Content-Type-Options','nosniff');
        $response->headers->set('X-Frame-Options','DENY');
        $response->headers->set('Referrer-Policy','no-referrer');
        $response->headers->set('Permissions-Policy','camera=(self), microphone=(), geolocation=()');
        if (!$request->is('/') && !$request->is('qris/*') && !$request->is('build/*')) {
            $response->headers->set('Cache-Control','private, no-store');
            $response->headers->set('X-Robots-Tag','noindex, nofollow');
        }
        return $response;
    }
}
