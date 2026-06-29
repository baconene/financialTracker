<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use App\Models\Category;
use App\Models\UserActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): \Illuminate\Http\RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
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
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create default categories
        $this->createDefaultCategories($user);

        Auth::login($user);
        return redirect('/onboarding');
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
        return redirect('/login');
    }

    private function createDefaultCategories(User $user): void
    {
        $categories = [
            ['name' => 'Salary', 'icon' => 'banknotes', 'color' => '#10B981', 'type' => 'income'],
            ['name' => 'Freelance', 'icon' => 'computer-desktop', 'color' => '#0EA5E9', 'type' => 'income'],
            ['name' => 'Business', 'icon' => 'building-storefront', 'color' => '#7C3AED', 'type' => 'income'],
            ['name' => 'Investment', 'icon' => 'chart-bar', 'color' => '#F59E0B', 'type' => 'income'],
            ['name' => 'Food & Dining', 'icon' => 'cake', 'color' => '#EF4444', 'type' => 'expense'],
            ['name' => 'Transportation', 'icon' => 'truck', 'color' => '#F59E0B', 'type' => 'expense'],
            ['name' => 'Utilities', 'icon' => 'bolt', 'color' => '#6366F1', 'type' => 'expense'],
            ['name' => 'Housing', 'icon' => 'home', 'color' => '#8B5CF6', 'type' => 'expense'],
            ['name' => 'Healthcare', 'icon' => 'heart', 'color' => '#EC4899', 'type' => 'expense'],
            ['name' => 'Shopping', 'icon' => 'shopping-bag', 'color' => '#F97316', 'type' => 'expense'],
            ['name' => 'Entertainment', 'icon' => 'film', 'color' => '#14B8A6', 'type' => 'expense'],
            ['name' => 'Education', 'icon' => 'academic-cap', 'color' => '#3B82F6', 'type' => 'expense'],
            ['name' => 'Personal Care', 'icon' => 'user', 'color' => '#D946EF', 'type' => 'expense'],
            ['name' => 'Savings', 'icon' => 'archive-box', 'color' => '#22C55E', 'type' => 'expense'],
            ['name' => 'Others', 'icon' => 'ellipsis-horizontal', 'color' => '#6B7280', 'type' => 'both'],
        ];

        foreach ($categories as $cat) {
            Category::create(array_merge($cat, ['user_id' => $user->id, 'is_system' => true]));
        }
    }
}

