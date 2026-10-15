<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller {
    public function login(Request $request) {
        $data=$request->validate(['email'=>'required|email|max:255','password'=>'required|string|max:255']);
        if (!Auth::attempt([...$data,'active'=>true])) {
            throw ValidationException::withMessages(['email'=>'Email atau kata sandi tidak cocok.']);
        }
        $request->session()->regenerate();
        return redirect()->intended('/admin');
    }
    public function logout(Request $request) {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect('/admin/login');
    }
}
