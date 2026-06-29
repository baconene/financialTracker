<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Find user manually so we can check is_admin before touching Auth state
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid admin credentials.'])->onlyInput('email');
        }

        if (!$user->is_admin) {
            return back()->withErrors(['email' => 'You do not have admin access.'])->onlyInput('email');
        }

        // Clear any existing session (regular user or stale admin)
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Log in the admin
        Auth::login($user);
        $request->session()->regenerate();

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
