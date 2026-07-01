<?php

namespace App\Services;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class UserEncryptionService
{
    private array $cache = [];

    public function encrypterFor(int $userId): Encrypter
    {
        if (!isset($this->cache[$userId])) {
            $encryptedKey = DB::table('users')->where('id', $userId)->value('data_key');

            if (!$encryptedKey) {
                $this->generateKeyForUser($userId);
                $encryptedKey = DB::table('users')->where('id', $userId)->value('data_key');
            }

            $rawKey = base64_decode(Crypt::decryptString($encryptedKey));
            $this->cache[$userId] = new Encrypter($rawKey, 'AES-256-CBC');
        }

        return $this->cache[$userId];
    }

    public function generateKeyForUser(int $userId): void
    {
        $encryptedKey = Crypt::encryptString(base64_encode(random_bytes(32)));
        DB::table('users')->where('id', $userId)->update(['data_key' => $encryptedKey]);
        unset($this->cache[$userId]);
    }
}
