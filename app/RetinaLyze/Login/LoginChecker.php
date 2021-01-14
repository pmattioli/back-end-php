<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Login;

use RetinaLyze\Database\DatabaseHandler;
use Defuse\Crypto\KeyProtectedByPassword;
use RetinaLyze\Crypto\Encryption;
use RetinaLyze\Utils\APIKeyGenerator;
use RetinaLyze\Crypto\Hasher;
use RetinaLyze\Login\UserExistsOtherRegions;
use RetinaLyze\Utils\Config;
use voku\helper\AntiXSS;
use GuzzleHttp\Client;



/**
 * Description of LoginChecker
 *
 * @author mom
 */
class LoginChecker {

    private $base_url;
    private $dbHandler;
    private $antiXss;

    function __construct($needDB = true) {
        if($needDB){
            $this->dbHandler = new DatabaseHandler();
        }
        $this->antiXss = new AntiXSS();
        $config = Config::getConfig();
        $this->base_url = $config["base_url"];
        $this->httpClient = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://back-end-services:8083/',
        ]);
    }

    public function checkLogin($ssl) {
        $uri = $_SERVER["REQUEST_URI"];
        //Redirect to SSL if wanted
        if ($ssl == true) {
            if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
                if(!empty($_SERVER['HTTP_HOST'])){
                    $redirect_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                }else{
                    $redirect_url = "https://" . $this->base_url . $_SERVER['REQUEST_URI'];
                }
                header("Location: $redirect_url");
                exit();
            }
        }
        //The user is trying to login
        $sanedPost = $this->antiXss->xss_clean($_POST);
        //$_GET['method'] is an important check because this is how the WAF identify and limit login attemps from the same IP
        if (isset($sanedPost['method']) && $sanedPost['method'] == 'login' && !empty($_GET['method']) && $_GET['method'] === 'login') {
            $username = $sanedPost['username'];
            $inputtedPassword = $sanedPost['password'];
            #Add toggle
            $response = $this->httpClient->request('POST', 'auth/login', [
                'json' => ['username' => $username, 'password' => $inputtedPassword]
            ]);
            $tokenJson = json_decode($response->getBody(),true);
            $_SESSION['authToken'] = $tokenJson["authToken"];
            $res = $this->dbHandler->getPasswordLoginInfo($username);
            $row = $res->fetch_assoc();
            if (!empty($row) && $this->verify($inputtedPassword, $row['password']) && $row['disabled'] != "1") {
                $this->login($row, $inputtedPassword);
            } else {
                $otherRegion = UserExistsOtherRegions::getOtherRegionWhereUserExists($username);
                if($otherRegion !== false){
                    //There was found another region where the user exists.
                    header('Location: https://' . $otherRegion . '/index.php?page=login&login=wrongRegion');
                    die("The password or username is wrong");
                }else{
                    //The password was wrong or the user doesn't exist.
                    header('Location: ' . $this->base_url . 'index.php?page=login&login=wrong');
                    die("The password or username is wrong");
                }
                
            }
        } elseif ($uri == "/index.php" || $uri == "/") {
            //Check if user is already logged in. If thats the case redirect to start page.
            if (!empty($_SESSION['pass_ok'])) {
                header('Location: ' . $this->base_url . 'index.php?page=start');
                die("You isn't logged in");
            }
            header('Location: ' . $this->base_url . 'index.php?page=login');
            die("You isn't logged in");
        } elseif ((!empty($_GET["page"]) && ($_GET["page"] == "login"))) {
            if (!empty($_GET['login'])) {
                if ($_GET['login'] == "logout") { //The user wants to logout
                    $_SESSION = array();
                    if (ini_get("session.use_cookies")) {
                        $params = session_get_cookie_params();
                        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]
                        );
                    }
                    session_destroy();
                } elseif ($_GET['login'] == "loginas") { //An admin wants to login as another user
                    //Make sure it is an admin user
                    if($this->getTypeID() === 1 && !empty($_GET['username'])){
                        
                        //Get the userlogin info
                        $loginAsUsername = $_GET['username'];
                        $userLoginInfoDB = $this->dbHandler->getPasswordLoginInfo($loginAsUsername);
                        $userLoginInfo = $userLoginInfoDB->fetch_assoc();
                        
                        //Only users with type 2 is allowed to login as.
                        if($userLoginInfo['typeID'] === 2){
                            //Logout before loggin in again as the other user
                            $_SESSION = array();
                            //Login as the other user
                            $this->login($userLoginInfo, null, true, true);
                        }
                    }
                }
            }
            //Check if user is already logged in. If thats the case redirect to start page.
            if (!empty($_SESSION['pass_ok'])) {
                header('Location: ' . $this->base_url . 'index.php?page=start');
                die("You isn't logged in");
            }
            //The user wants to login see the loging page (Dont do anything)
        } elseif (!empty($_GET["page"]) && $_GET['page'] == "detailed") {  //No login with pid key
            $res = $this->dbHandler->getSingleAnalyzedImgInfoWithUsername($_GET["id"]);
            if($res != false){
                $resrow = $res->fetch_assoc();
                $pid = $resrow["pid"];
                if (!empty($_GET["paid"])) {
                    if ($pid == $_GET["paid"]) {
                        return;
                    } elseif (empty($_SESSION['pass_ok'])) {
                        header('Location: ' . $this->base_url . 'index.php?page=login&login=not&dest=' . urlencode($_SERVER['REQUEST_URI']));
                        die("You isn't logged in");
                    }
                } elseif (empty($_SESSION['pass_ok'])) {
                    header('Location: ' . $this->base_url . 'index.php?page=login&login=not&dest=' . urlencode($_SERVER['REQUEST_URI']));
                    die("You isn't logged in");
                }
            }
        } elseif (empty($_SESSION['pass_ok'])) {
            //The user isn't logged in
            header('Location: ' . $this->base_url . 'index.php?page=login&login=not&dest=' . urlencode($_SERVER['REQUEST_URI']));
            die("You isn't logged in");
        }
    }

    private function login($row, $inputtedPassword, $skipUserKeyLoading = false, $skipUpdateLastLoginLogging = false) {
        $userid = $row['userID'];
        $_SESSION['UserID'] = $userid;
        $_SESSION['pass_ok'] = '1';
        $_SESSION['username'] = $row['username'];
        $_SESSION['typeID'] = $row["typeID"];
        $_SESSION['userRoleID'] = $row["roleID"];
        $_SESSION['referral'] = $row["referral"];
        $_SESSION['saveClientName'] = $row["saveCustomerName"];
        $_SESSION['AMDenabled'] = $row['AMDenabled'];
        $_SESSION['lbRegion'] = $row['lbRegion'];
        $_SESSION['onlyESB'] = $row['onlyESB'];
        $_SESSION['showCustomerNameInTables'] = $row["showCustomerNameInTables"];
        $_SESSION['extendedGlaucomaInfoEnabled'] = $row["extendedGlaucomaInfoEnabled"];
        $_SESSION['useNewImagePath'] = $row["s3usingUserID"];

        //Get users preffered language
        $userInfoDB = $this->dbHandler->getUserAdditionalInfoFromID($userid);
        $userInfo = $userInfoDB->fetch_assoc();
        $_SESSION['lang'] = $userInfo['language'];
        $_SESSION['timezone'] = $userInfo['timezone'];
        
        if(!$skipUserKeyLoading){
            //Get the user key
            try {
                //Start by checking if API keys and encryption key is available, if now generate them.
                $protected_key_encoded = $this->checkAPIKeysNotNullAndGenerate($userid, $row, $inputtedPassword);

                $protected_key = KeyProtectedByPassword::loadFromAsciiSafeString($protected_key_encoded);
                $user_key = $protected_key->unlockKey($inputtedPassword);
                $user_key_encoded = $user_key->saveToAsciiSafeString();
            } catch (\Exception $ex) {
                error_log("Error: Couldn't decode in login stage:" . $ex->getTraceAsString());
                $user_key_encoded = '';
            }

            $_SESSION['user_key_encoded'] = $user_key_encoded;
        }

        if(!$skipUpdateLastLoginLogging){
            //Update last login in DB
            $this->dbHandler->updateLastLogin($userid);
        }
        
        $sanedPost = $this->antiXss->xss_clean($_POST);
        if (!empty($sanedPost["dest"])) {
            $dest = urldecode($sanedPost['dest']);
            $destTrimmed = ltrim($dest, '/'); //Remove first char if its a /
            header('Location: ' . $this->base_url . $destTrimmed);
            die("Redirected");
        } else {
            header('Location: ' . $this->base_url . 'index.php?page=start');
            die("Redirected");
        }
    }

    public function checkLoginWithArg($username, $password) {
        $res = $this->dbHandler->getPasswordLoginInfo($username);
        $row = $res->fetch_assoc();
        if (empty($row)) {
            return false;
        } else {
            if ($this->verify($password, $row['password'])) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function checkAPIKeysNotNullAndGenerate($userID, $userInfo, $password) {
        $generateKeys = false;
        $protectedKeyEncoded = $userInfo['encryptionKey'];
        //Check if the encryptionKey is present
        if (empty($protectedKeyEncoded)) {
            //Generate the encrypted key from the users password.
            $protectedKey = Encryption::generateEncryptionKeyFromPassword($password);
            $protectedKeyEncoded = $protectedKey->saveToAsciiSafeString();
            $this->dbHandler->updateEncryptedKeyOnUser($userID, $protectedKeyEncoded);

            //Alwas generate new keys if the encryptionKey wasn't available.
            $generateKeys = true;
        }

        //Generate new API keys if missing
        if (empty($userInfo['api']) || empty($userInfo['secret_hashed']) || $generateKeys) {

            $api = APIKeyGenerator::generateAPIKey();
            $secret = APIKeyGenerator::generateAPIKey();
            try {
                $encryptedSecret = Encryption::encrypt($protectedKeyEncoded, $password, $secret);
                $hashed_secret = Hasher::generateHashFromString($secret);
            } catch (\Exception $ex) {
                error_log('Error: Generation of api keys error (on login): ' . $ex->getTraceAsString());
                die();
            }
            $this->dbHandler->updateSecretSecretHashedAndAPIOnUser($userID, $encryptedSecret, $hashed_secret, $api);
        }
        return $protectedKeyEncoded;
    }

    public function checkAPIKey($userid, $api, $secret) {
        $res = $this->dbHandler->getUserInfoWithAdditionalInfoFromID($userid);
        $row = $res->fetch_assoc();
        if (empty($row)) {
            return false;
        } else {
            if ($api == $row['api'] && $this->verify($secret, $row['secret_hashed'])) {
                $_SESSION['UserID'] = $userid;
                $_SESSION['pass_ok'] = '1';
                $_SESSION['username'] = $row['username'];
                $_SESSION['typeID'] = $row["typeID"];
                $_SESSION['userRoleID'] = $row["roleID"];
                $_SESSION['referral'] = $row["referral"];
                $_SESSION['saveClientName'] = $row["saveCustomerName"];
                $_SESSION['showCustomerNameInTables'] = $row["showCustomerNameInTables"];
                $_SESSION['extendedGlaucomaInfoEnabled'] = $row["extendedGlaucomaInfoEnabled"];
                $_SESSION['AMDenabled'] = $row['AMDenabled'];
                $_SESSION['lbRegion'] = $row['lbRegion'];
                $_SESSION['onlyESB'] = $row['onlyESB'];
                return $row['username'];
            } else {
                return false;
            }
        }
    }
    
    public function checkAPISecretKey($userid, $secret) {
        $res = $this->dbHandler->getUserInfoWithAdditionalInfoFromID($userid);
        $row = $res->fetch_assoc();
        if (empty($row)) {
            return false;
        } else {
            if ($this->verify($secret, $row['secret_hashed'])) {
                $_SESSION['UserID'] = $userid;
                $_SESSION['pass_ok'] = '1';
                $_SESSION['username'] = $row['username'];
                $_SESSION['typeID'] = $row["typeID"];
                $_SESSION['userRoleID'] = $row["roleID"];
                $_SESSION['referral'] = $row["referral"];
                $_SESSION['saveClientName'] = $row["saveCustomerName"];
                $_SESSION['showCustomerNameInTables'] = $row["showCustomerNameInTables"];
                $_SESSION['extendedGlaucomaInfoEnabled'] = $row["extendedGlaucomaInfoEnabled"];
                $_SESSION['AMDenabled'] = $row['AMDenabled'];
                $_SESSION['lbRegion'] = $row['lbRegion'];
                $_SESSION['onlyESB'] = $row['onlyESB'];
                return true;
            } else {
                return false;
            }
        }
    }

    private function generateHash($password) {
        if (defined("CRYPT_BLOWFISH") && CRYPT_BLOWFISH) {
            $salt = '$2y$11$' . substr(md5(uniqid(rand(), true)), 0, 22);
            return crypt($password, $salt);
        }
    }

    private function verify($password, $hashedPassword) {
        return crypt($password, $hashedPassword) == $hashedPassword;
    }

    function getUserRoleID() {
        if (empty($_SESSION['userRoleID'])) {
            return false;
        } else {
            return $_SESSION['userRoleID'];
        }
    }
    
    function getTypeID() {
        if (empty($_SESSION['typeID'])) {
            return false;
        } else {
            return $_SESSION['typeID'];
        }
    }

    function getReferralStatus() {
        if (empty($_SESSION['referral'])) {
            return false;
        } else {
            return $_SESSION['referral'];
        }
    }

    function getUsername() {
        if (empty($_SESSION['username'])) {
            return false;
        } else {
            return $_SESSION['username'];
        }
    }
    
    /**
     * Return false if no userID was found. Otherwise the userID is returned.
     * @return int
     */

    function getUserID() {
        if (empty($_SESSION['UserID'])) {
            return false;
        } else {
            return $_SESSION['UserID'];
        }
    }

    function getAMDenabled() {
        if (empty($_SESSION['AMDenabled'])) {
            return false;
        } else {
            return $_SESSION['AMDenabled'];
        }
    }

    function getLBRegion() {
        if (empty($_SESSION['lbRegion'])) {
            return false;
        } else {
            return $_SESSION['lbRegion'];
        }
    }
    
    function getUseNewImagePath() {
        if (empty($_SESSION['useNewImagePath'])) {
            return false;
        } else {
            return $_SESSION['useNewImagePath'];
        }
    }
    
    function getDBLang(){
        $userID = $this->getUserID();
        if(!empty($userID)){
            $additionalUserInfo = $this->dbHandler->getUserAdditionalInfoFromID($userID)->fetch_assoc();
            return $additionalUserInfo["language"];
        }else{
            return null;
        }
    }

    function getLang() {
        if (empty($_SESSION['lang'])) {
            return null;
        } else {
            return $_SESSION['lang'];
        }
    }

    function getTimezone() {
        if (empty($_SESSION['timezone'])) {
            return false;
        } else {
            return $_SESSION['timezone'];
        }
    }
    
    function getShowCustomerNameInTables() {
        if (!isset($_SESSION['showCustomerNameInTables'])) {
            return false;
        } else {
            if($_SESSION['showCustomerNameInTables'] == '1'){
                return true;
            }else{
                return false;
            }
        }
    }
    
    function getExtendedGlaucomaInfoEnabled() {
        if (!isset($_SESSION['extendedGlaucomaInfoEnabled'])) {
            return false;
        } else {
            if($_SESSION['extendedGlaucomaInfoEnabled'] == '1'){
                return true;
            }else{
                return false;
            }
        }
    }
    
    function getSaveClientName() {
        if (!isset($_SESSION['saveClientName'])) {
            return NULL;
        } else {
            return $_SESSION['saveClientName'];
        }
    }
    
    function getOnlyESBStatus() {
        if (!isset($_SESSION['onlyESB'])) {
            return false;
        } else {
            return $_SESSION['onlyESB'];
        }
    }    

    function setLang($lang) {
        $_SESSION['lang'] = $lang;
    }

    function setTimezone($timezone) {
        $_SESSION['timezone'] = $timezone;
    }
    
    function getCountry(){
        $userID = $this->getUserID();
        if(!empty($userID)){
            $additionalUserInfo = $this->dbHandler->getUserAdditionalInfoFromID($userID)->fetch_assoc();
            return $additionalUserInfo["country"];
        }else{
            return null;
        }
    }
    
    function getChainID(){
        $userID = $this->getUserID();
        if(!empty($userID)){
            $additionalUserInfo = $this->dbHandler->getUserInfoFromID($userID)->fetch_assoc();
            return $additionalUserInfo["chainID"];
        }else{
            return null;
        }
    }
    
    public function checkIfNotAnalyzedAndAnalyzedSeparated() {
        try {
            if($this->getChainID() != NULL){
                $chainDataDB = $this->dbHandler->getChainFromChainID($this->getChainID());
            }else{
                return false;
            }            
        } catch (\Exception $ex) {
            error_log("Error: Could not get chainID from userID, " . $ex->getFile() . ",Line: " .  $ex->getLine() . "\n Message: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
            return false;
        }
        if(!empty($chainDataDB->num_rows) && $chainDataDB->num_rows == 1){
            $chainData = $chainDataDB->fetch_assoc();
            if($chainData["seperate_not_and_analyzed"] == 1){
                return true;
            }
        }
        return false;
    }

    function getUserIDFromUsername($username) {
        $userInfoDB = $this->dbHandler->getUserInfo($username);
        $userInfoDBRow = $userInfoDB->fetch_assoc();
        if (empty($userInfoDBRow)) {
            return false;
        } else {
            return $userInfoDBRow["userID"];
        }
    }

}