<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ActiveAdmin {
    public function handle(Request $request, Closure $next) {
        if (!$request->user() || !\App\Models\User::whereKey($request->user()->id)->value('active')) {
            Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
            abort(403,'Akun tidak aktif. Hubungi administrator.');
        }
        return $next($request);
    }
}
