<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use App\Models\UserActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $users = User::withCount(['accounts', 'transactions'])
            ->with(['accounts:id,user_id,name,type,balance,currency,color'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($user) => [
                'id'                 => $user->id,
                'name'               => $user->name,
                'email'              => $user->email,
                'is_admin'           => $user->is_admin,
                'accounts_count'     => $user->accounts_count,
                'transactions_count' => $user->transactions_count,
                'total_balance'      => round($user->accounts->sum('balance'), 2),
                'accounts'           => $user->accounts->map(fn ($a) => [
                    'id'       => $a->id,
                    'name'     => $a->name,
                    'type'     => $a->type,
                    'balance'  => $a->balance,
                    'currency' => $a->currency ?? 'PHP',
                    'color'    => $a->color,
                ]),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'created_at'    => $user->created_at->toIso8601String(),
            ]);

        $stats = [
            'total_users'    => User::where('is_admin', false)->count(),
            'total_accounts' => Account::count(),
            'total_balance'  => round((float) Account::get(['balance'])->sum('balance'), 2),
            'active_today'   => UserActivityLog::whereDate('logged_in_at', today())
                                    ->distinct('user_id')
                                    ->count('user_id'),
        ];

        $recentActivity = UserActivityLog::with('user:id,name,email')
            ->orderBy('logged_in_at', 'desc')
            ->limit(100)
            ->get()
            ->map(fn ($log) => [
                'id'           => $log->id,
                'user'         => $log->user ? ['id' => $log->user->id, 'name' => $log->user->name, 'email' => $log->user->email] : null,
                'logged_in_at' => $log->logged_in_at->toIso8601String(),
                'logged_out_at'=> $log->logged_out_at?->toIso8601String(),
                'duration'     => $log->duration,
                'ip_address'   => $log->ip_address,
            ]);

        return Inertia::render('Admin/Dashboard', compact('users', 'stats', 'recentActivity'));
    }
}
