<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Как encrypted:array, но при смене APP_KEY не падает — возвращает null (нужно пересохранить интеграцию).
 */
class GracefulEncryptedArray implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);

            return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException) {
            return null;
        } catch (\JsonException) {
            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR));
    }
}
