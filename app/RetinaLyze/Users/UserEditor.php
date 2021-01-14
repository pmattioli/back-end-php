<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Users;

use RetinaLyze\Login\LoginChecker;
use RetinaLyze\Crypto\Hasher;
use RetinaLyze\Crypto\Encryption;
use RetinaLyze\Utils\APIKeyGenerator;
use RetinaLyze\Time\Timezone;
use RetinaLyze\Location\Countries;
use RetinaLyze\Users\DemoPrepaidAdjuster;
use RetinaLyze\Database\DatabaseHandler;
use RetinaLyze\Internationalization\LanguageHandler;
use RetinaLyze\Exception\PasswordIsWeakException;

/**
 * Description of UserEditor
 *
 * @author mom
 */
class UserEditor {

    private $dbh;
    

    function __construct() {
        $this->dbh = new DatabaseHandler();
    }

    public function getUsersFromChain($chainID) {
        $usersdb = $this->dbh->getUsersFromChain($chainID);
        $users = array();
        while ($user = $usersdb->fetch_assoc()) {
            $users[] = $user["username"];
        }
        return $users;
    }

    public function getDisabledUsersFromChain($chainID) {
        $usersdb = $this->dbh->getDisabledUsersFromChain($chainID);
        $users = array();
        while ($user = $usersdb->fetch_assoc()) {
            $users[] = $user["username"];
        }
        return $users;
    }

    public function getUsersFromCountryOutsideChain($country) {
        $usersdb = $this->dbh->getUsersFromCountryOutsideChain($country);
        $users = array();
        while ($user = $usersdb->fetch_assoc()) {
            $users[] = $user["username"];
        }
        return $users;
    }

    public function getDiabledUsersFromCountryOutsideChain($country) {
        $usersdb = $this->dbh->getDisabledUsersFromCountryOutsideChain($country);
        $users = array();
        while ($user = $usersdb->fetch_assoc()) {
            $users[] = $user["username"];
        }
        return $users;
    }

    public function findUsers($seachTerm) {
        $usersdb = $this->dbh->findUsers($seachTerm);
        $users = array();
        while ($user = $usersdb->fetch_assoc()) {
            $users[] = $user["username"];
        }
        return $users;
    }
    
    public function changePassword($userID, $newPassword, $oldPassword = null) {
        if ($oldPassword != null) {
            //Get user's encryption key
            $userDB = $this->dbh->getUserInfoFromID($userID);
            $userInfo = $userDB->fetch_assoc();
            $encryptionKeyEncoded = $userInfo["encryptionKey"];

            //Decrypt secret from old password.
            $secret = Encryption::decrypt($encryptionKeyEncoded, $oldPassword, $userInfo["secret_encrypted"]);

            $secretDetails = $this->getSecretEncryption($newPassword, $secret);

            //Save in DB
            $this->dbh->updateSecretAndEncryptedKeyOnUser($userID, $secretDetails["encryptedSecret"], $secretDetails["protectedKeyEncoded"]);
        } else {
            $this->generateNewSecret($userID, $newPassword, null);
        }
        $hashedPassword = Hasher::generateHashFromString($newPassword);
        return $this->dbh->changePassword($userID, $hashedPassword);
    }

    public function getSecretEncryption($password, $secret = null, $protectedKeyEncoded = null) {
        //Generate new secret if null
        if ($secret == null) {
            $secret = APIKeyGenerator::generateAPIKey();
        }
        //Generate new password protected key if not provided
        if ($protectedKeyEncoded == null) {
            //Encrypt the secret with the new password
            $protectedKey = Encryption::generateEncryptionKeyFromPassword($password);
            $protectedKeyEncoded = $protectedKey->saveToAsciiSafeString();
        }

        $encryptedSecret = Encryption::encrypt($protectedKeyEncoded, $password, $secret);

        return array("protectedKeyEncoded" => $protectedKeyEncoded, "encryptedSecret" => $encryptedSecret, "secret" => $secret);
    }

    public function generateNewSecret($userID, $password, $protectedKeyEncoded) {
        $secretDetails = $this->getSecretEncryption($password, null, $protectedKeyEncoded);
        //Generate secret hash
        $hashedSecret = Hasher::generateHashFromString($secretDetails["secret"]);

        //Save in DB
        $this->dbh->updateSecretSecretHashedAndEncryptedKeyOnUser($userID, $secretDetails["encryptedSecret"], $hashedSecret, $secretDetails["protectedKeyEncoded"]);
    }

    public function saveUserDetails($postData) {
        $userID = $postData["userID"];
        $mail = $postData["mail"];
        $companyName = $postData["companyname"];
        $contactPerson = $postData["contactperson"];
        $taxNo = $postData["tax_no"];
        $address = $postData["address"];
        $zipcode = $postData["zipcode"];
        $city = $postData["city"];
        $phone = $postData["phone"];
        $country = empty($postData["country"]) ? NULL : $postData["country"];
        $lang = empty($postData["lang"]) ? NULL : $postData["lang"];
        $chainID = empty($postData["chain"]) ? NULL : $postData["chain"];
        $lbRegionID = empty($postData["lbregions"]) ? NULL : $postData["lbregions"];
        $disabled = empty($postData["disabled"]) ? "0" : $postData["disabled"];
        $saveName = empty($postData["save_name"]) ? "0" : $postData["save_name"];
        $showCustomerNameInTables = empty($postData["showCustomerNameInTables"]) ? "0" : $postData["showCustomerNameInTables"];
        $extendedGlaucomaInfoEnabled = empty($postData["extendedGlaucomaInfoEnabled"]) ? "0" : $postData["extendedGlaucomaInfoEnabled"];
        //Demo and prepaid DR and AMD
        list($demoStatusDRandAMD, $prepaidStatusDRandAMD) = $this->getStatusVariables($postData["dr_amd_demo_prepaid_status"]);
        $limitDRandAMD = $this->getLimits($postData["dr_amd_limit_no"], $postData["dr_amd_limit_no_increment"]);
        $dateTimeExpiresDRandAMD = $this->getExpiryDate($postData["dr_amd_limit_date"]);
        $daysWhenActivatedDRandAMD = empty($postData["daysWhenActivatedDRandAMD"]) ? NULL : $postData["daysWhenActivatedDRandAMD"];

        //Demo and prepaid Glaucoma
        list($demoStatusGlaucoma, $prepaidStatusGlaucoma) = $this->getStatusVariables($postData["glaucoma_demo_prepaid_status"]);
        $limitGlaucoma = $this->getLimits($postData["glaucoma_limit_no"], $postData["glaucoma_limit_no_increment"]);
        $dateTimeExpiresGlaucoma = $this->getExpiryDate($postData["glaucoma_limit_date"]);
        $daysWhenActivatedGlaucoma = empty($postData["daysWhenActivatedGlaucoma"]) ? NULL : $postData["daysWhenActivatedGlaucoma"];

        //Demo and prepaid Ophthalmologist
        $limitOphthal = NULL;
        $dateTimeExpiresOphthal = NULL;
        $demoStatusOphthal = 0;
        $prepaidStatusOphthal = 0;

        $drEnabled = empty($postData["dr_enabled"]) ? "0" : $postData["dr_enabled"];
        $amdEnabled = empty($postData["amd_enabled"]) ? "0" : $postData["amd_enabled"];
        $glaucomaEnabled = empty($postData["glaucoma_enabled"]) ? "0" : $postData["glaucoma_enabled"];
        $onlyESB = empty($postData["only_esb"]) ? "0" : $postData["only_esb"];
        $referral = empty($postData["referral"]) ? "0" : $postData["referral"];
        $referralMail = empty($postData["referral_mail"]) ? "0" : $postData["referral_mail"];
        $camera = empty($postData["camera"]) ? NULL : $postData["camera"];
        $userType = $postData["user_type"];
        $userRole = empty($postData["user_role"]) ? NULL : $postData["user_role"];
        $timezone = $postData["timezone"];

        $drOffset = empty($postData["drOffset"]) ? 0 : $postData["drOffset"];
        $amdOffset = empty($postData["amdOffset"]) ? 0 : $postData["amdOffset"];

        //Offset values:
        if (!is_numeric($drOffset) || !is_numeric($amdOffset)) {
            return false;
        }

        //Get current user type, if not one (admin) check that it isn't changed to one.
        $userInfoDB = $this->dbh->getUserInfoFromID($userID);
        $userInfo = $userInfoDB->fetch_assoc();
        if ($userType == "1" && $userInfo["typeID"] != "1") {
            //Not allowed to change user to admin
            return false;
        }

        //Change password if it wasn't blank
        if (!empty($postData["change_password"]) && $postData["change_password"] == "1") {
            $password = $postData["password"];
            //Change password
            $this->changePassword($userID, $password);
        }

        try {
            //Update user details
            $this->dbh->updateUser($userID, $mail, $userType, $disabled, $userRole);

            //Update user settings
            $this->dbh->updateUserSettings($userID, $amdEnabled, $referral, $referralMail, $saveName, $drOffset, $amdOffset, $onlyESB, $drEnabled, $glaucomaEnabled, $showCustomerNameInTables, $extendedGlaucomaInfoEnabled);

            //Update additional info
            $this->dbh->updateAdditionalInfoOnUser($userID, $companyName, $contactPerson, $taxNo, $address, $zipcode, $city, $phone, $country, $lang, $timezone, $camera);

            //Update chain
            $this->dbh->updateChainForUser($userID, $chainID);

            //Update LB region
            $this->dbh->updateLBRegionForUser($userID, $lbRegionID);

            //Update Demo status
            $dsa = new DemoPrepaidAdjuster($userID);
            $dsa->setDRandAMDDemoPrepaidStatus($demoStatusDRandAMD, $dateTimeExpiresDRandAMD, $limitDRandAMD, $prepaidStatusDRandAMD, $daysWhenActivatedDRandAMD);
            $dsa->setGlaucomaDemoPrepaidStatus($demoStatusGlaucoma, $dateTimeExpiresGlaucoma, $limitGlaucoma, $prepaidStatusGlaucoma, $daysWhenActivatedGlaucoma);
            $dsa->updateOphthalDemoStatusAndLimit($demoStatusOphthal, $dateTimeExpiresOphthal, $limitOphthal, $prepaidStatusOphthal);
        } catch (\Exception $ex) {
            error_log('Error: Could not save user details. File ' . $ex->getFile() . ', line: ' . $ex->getLine() . ', Message: ' . $ex->getMessage() . ', Stack: ' . $ex->getTraceAsString());
            return false;
        }
        return true;
    }

    private function getStatusVariables($statusParameter) {
        if (empty($statusParameter)) {
            $demoStatus = 0;
            $prepaidStatus = 0;
        } else {
            switch ($statusParameter) {
                case 'prepaid':
                    $prepaidStatus = 1;
                    $demoStatus = 0;
                    break;
                case 'prepaid_not':
                    $prepaidStatus = 2;
                    $demoStatus = 0;
                    break;
                case 'demo':
                    $prepaidStatus = 0;
                    $demoStatus = 1;
                    break;
                case 'demo_not':
                    $prepaidStatus = 0;
                    $demoStatus = 2;
                    break;
                case 'paying':
                    $prepaidStatus = 0;
                    $demoStatus = 0;
                    break;
                default:
                    $prepaidStatus = 0;
                    $demoStatus = 0;
                    break;
            }
        }
        return array($demoStatus, $prepaidStatus);
    }

    private function getLimits($limitNumber, $limitIncrement) {
        $limitNo = !isset($limitNumber) || $limitNumber == '' ? NULL : $limitNumber;
        $limitNoIncrement = empty($limitIncrement) ? 0 : $limitIncrement;
        if (!(is_numeric($limitNo) || $limitNo == NULL) || !is_numeric($limitNoIncrement)) {
            return false;
        }
        if (!empty($limitNoIncrement)) {
            $limitNo = (int) $limitNo + (int) $limitNoIncrement;
        }
        return $limitNo;
    }

    private function getExpiryDate($date) {
        $dateTime = empty($date) ? NULL : $date;
        //Check expiry date
        if ($dateTime !== NULL) {
            $dateCheck = \DateTime::createFromFormat("Y-m-d", $dateTime);
            if ($dateCheck === false || array_sum($dateCheck->getLastErrors())) {
                error_log("Error: The following date could not be parsed: " . $expiryDate);
                return false;
            }
            $dateCheck->setTime(23, 59);
            return $dateCheck->format('Y-m-d H:i:s');
        } else {
            return NULL;
        }
    }

    public function saveUserDetailsPublic($postData, $userID, $username) {
        $loginChecker = new LoginChecker();
        if (!empty($postData["change_password"]) && $postData["change_password"] == "1") {
            $current_password = $postData["current_password"];
            $new_password = $postData["password"];
            $new_password_retyped = $postData["password_retyped"];
            
            //Check for weak password
            $this->checkForWeakPassword($new_password);
            
            //Check that new password and new password retyped match
            if ($new_password != $new_password_retyped) {
                throw new \Exception("The two passwords does not match", 1);
            }
            //Check that the current password is valid            
            if (!$loginChecker->checkLoginWithArg($username, $current_password)) {
                throw new \Exception("Wrong current password", 2);
            }
            //Change password
            $this->changePassword($userID, $new_password, $current_password);
        }
        $contact_mail = $postData["contact_mail"];
        $lang = empty($postData["lang"]) ? NULL : $postData["lang"];
        //Get Timezone
        $config = \RetinaLyze\Utils\Config::getConfig();
        $timezone = empty($postData["timezone"]) ? $config["default_timezone"] : $postData["timezone"];

        $referral_mail = empty($postData["referral_mail"]) ? "0" : $postData["referral_mail"];

        //Update user details
        $this->dbh->updateUserMail($userID, $contact_mail, $referral_mail);

        //Update additional info
        $this->dbh->updateLangAndTimezoneOnUser($userID, $lang, $timezone);

        //Update session data so language and timezone is updated
        $loginChecker->setLang($lang);
        $loginChecker->setTimezone($timezone);

        //check if a new secret should be generated
        if (!empty($postData["generate_new_secret"]) && $postData["generate_new_secret"] == "1") {
            $current_password = $postData["current_password_secret_generation"];
            //Check that the current password is valid            
            if (!$loginChecker->checkLoginWithArg($username, $current_password)) {
                throw new \Exception("Wrong current password", 2);
            }
            //Get user's password protected encryption key
            $userDB = $this->dbh->getUserInfoFromID($userID);
            $userInfo = $userDB->fetch_assoc();
            $encryptionKeyEncoded = $userInfo["encryptionKey"];
            $this->generateNewSecret($userID, $current_password, $encryptionKeyEncoded);
        }
    }

    public function createRegion($postData) {
        $regionTitle = $postData["regiontitle"];
        $country = $postData["country"];

        //Create region
        try {
            $this->dbh->createLBRegion($regionTitle, $country);
        } catch (\Exception $ex) {
            return false;
        }
        return true;
    }

    public function getUserInfo($username) {
        $userInfoDB = $this->dbh->getUserInfoWithAdditionalInfo($username);
        $userInfo = $userInfoDB->fetch_assoc();
        $demoStatusDB = $this->dbh->getDemoStatus($userInfo["id"]);
        if ($demoStatusDB != false) {
            $demoStatus = $demoStatusDB->fetch_assoc();
        }
        if (isset($demoStatus)) {
            return array_merge($userInfo, $demoStatus);
        } else {
            return $userInfo;
        }
    }

    public function getAllCountries() {
        return Countries::getContryArray();
    }

    public function getCurrentlyUsedCountries() {
        $contriesdb = $this->dbh->getCurrentlyUsedCounties();
        $countries = array();
        while ($country = $contriesdb->fetch_assoc()) {
            $countries[] = $country["country"];
        }
        return $countries;
    }

    public function getChainsFromCountry($country) {
        $chaindb = $this->dbh->getChainsFromCountry($country);
        $chains = array();
        while ($chain = $chaindb->fetch_assoc()) {
            $chains[] = array("id" => $chain["chainID"], "name" => $chain["name"]);
        }
        return $chains;
    }

    public function getChainSlectList($currentChainID) {
        $chainsdb = $this->dbh->getChains();
        $return = "<select name='chain'>";
        $return .= "<option value=''></option>";
        while ($chain = $chainsdb->fetch_assoc()) {
            $chainID = $chain["chain_id"];
            $chainName = $chain["name"];
            if ($currentChainID == $chainID) {
                $return .= "<option value='" . $chainID . "' selected>" . $chainName . "</option>";
            } else {
                $return .= "<option value='" . $chainID . "'>" . $chainName . "</option>";
            }
        }
        $return .= "</select>";

        return $return;
    }

    public function getCameraList($currentCameraID) {
        $cameradb = $this->dbh->getCameras();
        $return = "<select name='camera'>";
        $return .= "<option value=''>N/A</option>";
        while ($camera = $cameradb->fetch_assoc()) {
            $cameraID = $camera["cameraID"];
            $cameraName = $camera["cameraName"];
            if ($currentCameraID == $cameraID) {
                $return .= "<option value='" . $cameraID . "' selected>" . $cameraName . "</option>";
            } else {
                $return .= "<option value='" . $cameraID . "'>" . $cameraName . "</option>";
            }
        }
        $return .= "</select>";

        return $return;
    }

    public function getCountriesSlectList($selected) {
        $return = "<select name='country'>";
        if ($selected == NULL) {
            $return .= "<option value=''></option>";
        }
        foreach (Countries::getContryArray() as $countryCode => $countryName) {
            if ($selected == $countryCode) {
                $return .= "<option value='" . $countryCode . "' selected>" . $countryName . "</option>";
            } else {
                $return .= "<option value='" . $countryCode . "'>" . $countryName . "</option>";
            }
        }
        $return .= "</select>";

        return $return;
    }

    public function getCountryName($selectedCountryCode) {
        foreach (Countries::getContryArray() as $countryCode => $countryName) {
            if ($selectedCountryCode == $countryCode) {
                return $countryName;
            }
        }
        return "N/A";
    }

    public function getLanguagesSelectList($selected) {
        $lc = new LoginChecker();
        $lh = new LanguageHandler($lc->getLang());
        $availableLangs = $lh->getAvailableLangs();

        $return = "<select name='lang'>";
        foreach ($availableLangs as $langCode => $langArray) {
            if ($selected == $langCode) {
                $return .= "<option value='" . $langCode . "' selected>" . $langArray . "</option>";
            } else {
                $return .= "<option value='" . $langCode . "'>" . $langArray . "</option>";
            }
        }
        if ($selected == NULL) {
            $return .= "<option value='' selected>N/A (english)</option>";
        }
        $return .= "</select>";

        return $return;
    }

    public function getLBRegionSelectList($currentLBRegion, $country) {
        $lbRegionsdb = $this->dbh->getLBRegions();
        $return = "<select name='lbregions'>";
        if ($currentLBRegion == NULL) {
            $return .= "<option value='' selected>None</option>";
        } else {
            $return .= "<option value=''>None</option>";
        }
        while ($lbRegion = $lbRegionsdb->fetch_assoc()) {
            $regionID = $lbRegion["regID"];
            $regionName = $lbRegion["title"];
            if ($currentLBRegion == $regionID) {
                $return .= "<option value='" . $regionID . "' selected>" . $regionName . "</option>";
            } else {
                $return .= "<option value='" . $regionID . "'>" . $regionName . "</option>";
            }
        }

        $return .= "</select>";

        return $return;
    }

    public function getUserRoleSelectList($currentUserRole) {
        $userRolesDB = $this->dbh->getUserRoles();
        $return = "<select name='user_role'>";
        if ($currentUserRole == NULL) {
            $return .= "<option value='' selected>" . _('N/A') . "</option>";
        } else {
            $return .= "<option value=''>" . _('N/A') . "</option>";
        }
        while ($userRole = $userRolesDB->fetch_assoc()) {
            if ($currentUserRole == $userRole["userRoleID"]) {
                $return .= "<option value='" . $userRole["userRoleID"] . "' selected>" . $userRole["description"] . "</option>";
            } else {
                $return .= "<option value='" . $userRole["userRoleID"] . "'>" . $userRole["description"] . "</option>";
            }
        }
        $return .= "</select>";
        return $return;
    }

    public function getUserTypeSelectList($currentUserType) {
        $userTypesDB = $this->dbh->getUserTypes();
        $return = "<select name='user_type'>";
        while ($userType = $userTypesDB->fetch_assoc()) {
            if ($currentUserType == $userType["typeID"]) {
                $return .= "<option value='" . $userType["typeID"] . "' selected>" . $userType["typeName"] . "</option>";
            } else {
                $return .= "<option value='" . $userType["typeID"] . "'>" . $userType["typeName"] . "</option>";
            }
        }
        $return .= "</select>";
        return $return;
    }

    public function getTimezoneList($currentTimezone) {
        $timezones = Timezone::getHumanReadableTimezones();
        $return = '<select id="timezone" name="timezone">';
        foreach ($timezones as $region => $list) {
            $return .= '<optgroup label="' . $region . '">' . "\n";
            foreach ($list as $timezone => $name) {
                if ($timezone == $currentTimezone) {
                    $return .= '<option value="' . $timezone . '" selected>' . $name . '</option>' . "\n";
                } else {
                    $return .= '<option value="' . $timezone . '">' . $name . '</option>' . "\n";
                }
            }
            $return .= '</optgroup>' . "\n";
        }
        $return .= '</select>';
        return $return;
    }

    public function updateTimezone($userID, $newTimezone) {
        //Check if Timezone is accepted by PHP
        try {
            $mars = new \DateTimeZone($newTimezone);
        } catch (Exception $e) {
            throw new \RuntimeException('Could not validate new Timezone');
        }
        if (!empty($userID)) {
            $this->dbh->updateTimezoneOnUser($userID, $newTimezone);
        }
    }

    private function checkForWeakPassword($password) {

        if (strlen($password) < 10) {
            throw new PasswordIsWeakException(_('Passwords must be at least 10 characters in length'));
        }

        if (!preg_match("#[0-9]+#", $password)) {
            throw new PasswordIsWeakException(_('Passwords must contain a minimum of 1 numeric character [0-9]'));
        }

        if (!preg_match("#[a-z]+#", $password)) {
            throw new PasswordIsWeakException(_('Passwords must contain a minimum of 1 lower case letter [a-z]'));
        }

        if (!preg_match("#[A-Z]+#", $password)) {
            throw new PasswordIsWeakException(_('Passwords must contain a minimum of 1 upper case letter [A-Z]'));
        }
    }

}
