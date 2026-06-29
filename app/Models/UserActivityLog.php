<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'logged_in_at', 'logged_out_at', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'logged_in_at'  => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->logged_out_at) return null;
        $mins = (int) $this->logged_in_at->diffInMinutes($this->logged_out_at);
        if ($mins < 1) return '<1m';
        return $mins < 60 ? "{$mins}m" : (floor($mins / 60) . 'h ' . ($mins % 60) . 'm');
    }
}
