<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response|\Illuminate\Http\RedirectResponse
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect('/admin/dashboard');
        }
        return Inertia::render('Admin/Login');
    }

    public function login(Request $request): \Illuminate\Http\RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Temporarily log out any non-admin session so attempt works cleanly
        if (Auth::check() && !Auth::user()->is_admin) {
            Auth::logout();
        }

        if (Auth::attempt($credentials, false)) {
            if (!Auth::user()->is_admin) {
                Auth::logout();
                return back()->withErrors(['email' => 'You do not have admin access.'])->onlyInput('email');
            }

            $request->session()->regenerate();

            $user = Auth::user();
            $user->last_login_at = now();
            $user->save();

            UserActivityLog::create([
                'user_id'      => $user->id,
                'logged_in_at' => now(),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
            ]);

            return redirect('/admin/dashboard');
        }

        return back()->withErrors(['email' => 'Invalid admin credentials.'])->onlyInput('email');
    }

    public function logout(Request $request): \Illuminate\Http\RedirectResponse
    {
        UserActivityLog::where('user_id', Auth::id())
            ->whereNull('logged_out_at')
            ->latest('logged_in_at')
            ->first()?->update(['logged_out_at' => now()]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    }
}
