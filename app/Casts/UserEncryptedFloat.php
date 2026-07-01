<?php

namespace App\Casts;

use App\Services\UserEncryptionService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class UserEncryptedFloat implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        if ($value === null) return null;

        $userId = $attributes['user_id'] ?? null;

        if ($userId) {
            try {
                return (float) app(UserEncryptionService::class)->encrypterFor((int) $userId)->decryptString($value);
            } catch (\Exception) {}
        }

        // Fallback: value was encrypted with APP_KEY (pre-migration)
        try {
            return (float) Crypt::decryptString($value);
        } catch (\Exception) {}

        // Fallback: plain numeric (pre-encryption)
        return (float) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) return null;

        $userId = $attributes['user_id'] ?? null;

        if ($userId) {
            return app(UserEncryptionService::class)->encrypterFor((int) $userId)->encryptString((string) $value);
        }

        return Crypt::encryptString((string) $value);
    }
}
