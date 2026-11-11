<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }
    public function showRegister() { return view('auth.register'); }

    public function login(Request $r)
    {
        $c = $r->validate(['email' => 'required|email', 'password' => 'required']);
        if (!Auth::attempt($c, $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email or password is incorrect.'])->onlyInput('email');
        }
        if (!Auth::user()->isActive()) {
            Auth::logout();
            $r->session()->invalidate();
            $r->session()->regenerateToken();
            return back()->withErrors(['email' => 'This account is suspended. Contact an admin.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        return redirect()->route('home');
    }

    public function register(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);
        $user = User::create($d + ['role' => 'guest', 'status' => 'active']);
        Auth::login($user);
        $r->session()->regenerate();
        return redirect()->route('home');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }
}
