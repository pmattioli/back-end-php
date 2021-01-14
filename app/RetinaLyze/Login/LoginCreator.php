<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Login;

use RetinaLyze\Utils\APIKeyGenerator;
use RetinaLyze\Crypto\Hasher;
use RetinaLyze\Crypto\Encryption;
use RetinaLyze\Users\DemoPrepaidAdjuster;
use RetinaLyze\Utils\Config;
use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of LoginCreator
 *
 * @author mom
 */
class LoginCreator {

    public function create($array) {
        if(isset($array["user"]) && strlen($array["user"]) > 100){
            throw new \RuntimeException("User input is above 100 lenght");
        }
        $username = $array["user"];
        $password = $array["password"];
        $hashed_password = Hasher::generateHashFromString($array["password"]);
        $trialDRAndAMD = empty($array["trialDRAndAMD"]) ? false : true;
        $trailGlaucoma = empty($array["trailGlaucoma"]) ? false : true;
        
        if(isset($array["companyname"]) && strlen($array["companyname"]) > 100){
            throw new \RuntimeException("companyname input is above 100 lenght");
        }
        $company = $array["companyname"];
        if(isset($array["contactperson"]) && strlen($array["contactperson"]) > 100){
            throw new \RuntimeException("contactperson input is above 100 lenght");
        }
        $contact = $array["contactperson"];
        if(isset($array["tax_no"]) && strlen($array["tax_no"]) > 25){
            throw new \RuntimeException("tax_no input is above 25 lenght");
        }
        $tax_no = $array["tax_no"];
        if(isset($array["address"]) && strlen($array["address"]) > 150){
            throw new \RuntimeException("address input is above 100 lenght");
        }
        $address = $array["address"];
        if(isset($array["zipcode"]) && strlen($array["zipcode"]) > 6){
            throw new \RuntimeException("zipcode input is above 6 lenght");
        }
        $zipcode = $array["zipcode"];
        if(isset($array["city"]) && strlen($array["city"]) > 62){
            throw new \RuntimeException("city input is above 62 lenght");
        }
        $city = $array["city"];
        if(isset($array["phone"]) && strlen($array["phone"]) > 30){
            throw new \RuntimeException("phone input is above 30 lenght");
        }
        $phone = $array["phone"];
        $country = $array["country"];
        $chainID = empty($array["chain"]) ? NULL : $array["chain"];
        $lang = empty($array["lang"]) ? NULL : $array["lang"];
        $refferal = $array["refferal"];
        $camera = empty($array["camera"]) ? NULL : $array["camera"];
        $api = APIKeyGenerator::generateAPIKey();
        $secret = APIKeyGenerator::generateAPIKey();

        //Encrypt the secret with the new password
        $protectedKey = Encryption::generateEncryptionKeyFromPassword($password);
        $protectedKeyEncoded = $protectedKey->saveToAsciiSafeString();
        $encryptedSecret = Encryption::encrypt($protectedKeyEncoded, $password, $secret);

        //Hash secret
        $hashed_secret = Hasher::generateHashFromString($secret);
        
        $lbRegionID = empty($array["lbregions"]) ? NULL : $array["lbregions"];

        //Get default timezone
        $config = Config::getConfig();
        $timezone = $config["default_timezone"];

        $dbh = new DatabaseHandler();
        $userID = $dbh->createUser($username, $hashed_password, $company, $contact, $tax_no, $address, $zipcode, $city, $phone, $country, $chainID, $refferal, $lang, $protectedKeyEncoded, $api, $hashed_secret, $encryptedSecret, $timezone, $camera, $trailGlaucoma, $lbRegionID);
        $dsa = new DemoPrepaidAdjuster($userID);
        $dsa->setDRandAMDDemoStatusStandard($trialDRAndAMD);
        $dsa->setGlaucomaDemoStatusStandard($trailGlaucoma);
        return true;
    }

}