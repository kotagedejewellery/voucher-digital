<?php
namespace App\Http\Controllers;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
class AuthController extends Controller {
    public function showLogin(): View {
        return view('auth.login',['support'=>Setting::read('support_phone')]);
    }
    public function throttled(Request $request, int $seconds): Response {
        $request->flashOnly('email');
        return response()->view('auth.login',['support'=>Setting::read('support_phone'),'throttleSeconds'=>$seconds],429);
    }
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
        return redirect('/admin/login')->with('success','Anda sudah keluar dari panel.')->with('feedback','toast');
    }
}
