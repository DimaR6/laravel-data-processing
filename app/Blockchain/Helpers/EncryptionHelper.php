<?php

namespace App\Blockchain\Helpers;

use Illuminate\Encryption\Encrypter;

class EncryptionHelper
{
    private static $encrypter = null;

    private static function getEncrypter()
    {
        if (self::$encrypter === null) {
            $key = self::getEncryptionKey();
            self::$encrypter = new Encrypter($key, '');
        }
        return self::$encrypter;
    }

    private static function getEncryptionKey()
    {
        $key = env('ENCRYPTION_KEY');
        if (!$key) {
            throw new \Exception('Custom encryption key is not set');
        }
        return base64_decode($key);
    }

    public static function encrypt($value)
    {
        return self::getEncrypter()->encrypt($value);
    }

    public static function decrypt($value)
    {
        return self::getEncrypter()->decrypt($value);
    }

    public static function isZeroBalance($balance, $decimals = 18)
    {
        $balance = number_format((float)$balance, $decimals, '.', '');
        $smallestUnit = bcmul($balance, bcpow('10', $decimals, 0), 0);

        return $smallestUnit === '0';
    }
}