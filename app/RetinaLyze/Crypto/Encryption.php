<?php namespace RetinaLyze\Crypto;

/*
 * All Rights Reserved RetinaLyze System.
 */

use Defuse\Crypto\KeyProtectedByPassword;
use Defuse\Crypto\Key;
use Defuse\Crypto\Crypto;

/**
 * Encryption is used to decrypt and encrypt strings with an encryption key generated from a password.
 *
 * @author mom
 */

class Encryption {
    /**
     * Decrypt an encrypted string with an encryption key
     * 
     * @param string $encryptionKeyEncoded
     * @param Key $userPassword
     * @param string $encryptedString
     * @return string
     * @throws Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException
     */
    public static function decrypt($encryptionKeyEncoded, $userPassword, $encryptedString){
        //Decrypt secret from old password.
        $encryption_key = KeyProtectedByPassword::loadFromAsciiSafeString($encryptionKeyEncoded);
        $userKey = $encryption_key->unlockKey($userPassword);
        return Crypto::decrypt($encryptedString, $userKey);
    }
    
    /**
     * Encrypt the string with an encryption key and a user password.
     * @param string $protectedKeyEncoded
     * @param Key $userPassword
     * @param string $string
     * @return string
     * @throws Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public static function encrypt($protectedKeyEncoded, $userPassword, $string){
        $protected_key = KeyProtectedByPassword::loadFromAsciiSafeString($protectedKeyEncoded);
        $userKey = $protected_key->unlockKey($userPassword);
        return Crypto::encrypt($string, $userKey);
    }
    
    /**
     * Generates a encryption key with a user password.
     * @param string $password
     * @return KeyProtectedByPassword
     */
    public static function generateEncryptionKeyFromPassword($password){
        return KeyProtectedByPassword::createRandomPasswordProtectedKey($password); 
    }
}
