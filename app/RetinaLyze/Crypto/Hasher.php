<?php namespace RetinaLyze\Crypto;

/*
 * All Rights Reserved RetinaLyze System.
 */

/**
 * Hasher generates hashes used by the app.
 *
 * @author mom
 */
class Hasher {
    public static function generateHashFromString($string){
        if (defined("CRYPT_BLOWFISH") && CRYPT_BLOWFISH) {
            $salt = '$2y$11$' . substr(md5(uniqid(rand(), true)), 0, 22);
            return crypt($string, $salt);
        }
    }
}
