<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Database;

use RetinaLyze\Utils\Config;
use RetinaLyze\Exception\NameChangeDetected;
use RetinaLyze\Time\DatabaseTime;

/**
 * Description of DatabaseHandler
 *
 * @author mom
 */
class DatabaseHandler {

    private $mysqli;
    private $config;

    function __construct() {
        $this->config = Config::getConfig();
        //The p: prefix enables persistent connections.
        $mysqli = mysqli_init();
        try{
            $mysqli->real_connect("p:" . $this->config["server_address"], $this->config["username"], $this->config["password"], $this->config["db_name"], 3306, null, MYSQLI_CLIENT_SSL);
        } catch (\Exception $ex) {
            error_log("Error: Could not connect to DB: " . $ex->getMessage() . ' in file ' . $ex->getFile() . ', line: '. $ex->getLine()  . ', '  . $ex->getTraceAsString());
            exit("We are currently updating. We will be back in a few minutes");
        }
        $mysqli->set_charset('utf8');
        if ($mysqli->connect_errno) {
            exit("We are currently updating. We will be back in a few minutes");
        }
        $this->mysqli = $mysqli;
    }

    /////////////////////////////////////////
    // Functions related to the analyze
    /////////////////////////////////////////
    
    public function updateAfterAnalyzeNew($id, $numberAnn, $type, $quality, $annData, $opticNerveHeadFound) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $mysqldate = DatabaseTime::getCurrentTime();
        if ($type == 0) {
            $query = "UPDATE ret_image_info SET annotationsDR = ?, timeAnalyzedDR = ?, quality = ?, annDRData = ?, opticNerveHeadFound = ?, runningAnalysisDR = 0 WHERE id = ?;";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("isisii", $numberAnn, $mysqldate, $quality, $annData, $opticNerveHeadFound, $id);
            $stmt->execute();
        } elseif ($type == 1) {
            $query = "UPDATE ret_image_info SET annotationsAMD = ?, timeAnalyzedAMD = ?, quality = ?, annAMDData = ?, opticNerveHeadFound = ?, runningAnalysisAMD = 0 WHERE id = ?;";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("isisii", $numberAnn, $mysqldate, $quality, $annData, $opticNerveHeadFound, $id);
            $stmt->execute();
        }
    }
    
    public function updateAfterGlaucomaAnalyze($id, $glaucomaResult, $glaucomaID, $time, $checkEye, $cdRatioArea, $cdRatioVert, $gdf, $focusQuality, $thresholds, $glaucomaVersion) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->mysqli->autocommit(false);
            $query = "UPDATE ret_image_info SET glaucomaResult = ? WHERE id = ?;";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("ii", $glaucomaResult, $id);
            $stmt->execute();
            $query2 = "UPDATE `ret_glaucoma_info` SET analysisDuration = ?, checkEyeSideReturned = ?, cdRatioArea = ?, cdRatioVert = ?, gdf = ?, focusQuality = ?, glaucomaVersion = ?, gdfRedThreshold = ?, gdfOrangeThreshold = ?, gdfYellowThreshold = ?, cdVertRedThreshold = ?, cdVertOrangeThreshold = ?, cdVertYellowThreshold = ?, cdAreaRedThreshold = ?, cdAreaOrangeThreshold = ?, cdAreaYellowThreshold = ? WHERE glaucomaID = ?;";
            $stmt2 = $this->mysqli->prepare($query2);
            $stmt2->bind_param("didddiidddddddddi", $time, $checkEye, $cdRatioArea, $cdRatioVert, $gdf, $focusQuality, $glaucomaVersion, $thresholds[0], $thresholds[1], $thresholds[2], $thresholds[3], $thresholds[4], $thresholds[5], $thresholds[6], $thresholds[7], $thresholds[8], $glaucomaID);
            $stmt2->execute();
        } catch (\Exception $ex) {
            $this->mysqli->rollback();
            throw $ex;
        } finally {
            $this->mysqli->autocommit(true);
        }
        return $glaucomaID;
    }
    
    public function updateAfterConfirmedBorder($id, $customOHNCoordinates, $customOHNEllipse) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query2 = "UPDATE `ret_glaucoma_info` SET customOHNCoordinates = ?, customOHNEllipse = ? WHERE glaucomaID = ?;";
        $stmt2 = $this->mysqli->prepare($query2);
        $stmt2->bind_param("ssi", $customOHNCoordinates, $customOHNEllipse, $id);
        $stmt2->execute();
    }
    
    public function initializeGlaucomaAnalysis($imgID, $apiID){        
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $mysqldate = DatabaseTime::getCurrentTime();
        try {
            $this->mysqli->autocommit(false);
            $query = "INSERT INTO `ret_glaucoma_info` (`apiID`) VALUES (?);";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("i", $apiID);
            $stmt->execute();

            $glaucomaID = $this->mysqli->insert_id;

            $query2 = "UPDATE `ret_image_info` SET glaucomaID = ?, timeGlaucomaStarted = ? WHERE id = ?;";
            $stmt2 = $this->mysqli->prepare($query2);
            $stmt2->bind_param("isi", $glaucomaID, $mysqldate, $imgID);
            $stmt2->execute();
        } catch (\Exception $ex) {
            $this->mysqli->rollback();
            throw $ex;
        } finally {
            $this->mysqli->autocommit(true);
        }
        return $glaucomaID;
    }
    
    public function updateIsSaturated($imgID, $isSaturated){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query2 = "UPDATE `ret_image_info` INNER JOIN ret_glaucoma_info ON ret_glaucoma_info.glaucomaID = ret_image_info.glaucomaID SET isSaturated = ?, glaucomaResult = 0, `analysisErrorCode` = 6 WHERE id = ?;";
        $stmt2 = $this->mysqli->prepare($query2);
        $stmt2->bind_param("ii", $isSaturated, $imgID);
        $stmt2->execute();
    }
    
    public function updateGlaucomaAnalysisError($imgID, $errorStatus, $errorCode){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query2 = "UPDATE `ret_image_info` INNER JOIN ret_glaucoma_info ON ret_glaucoma_info.glaucomaID = ret_image_info.glaucomaID SET glaucomaAnalysisError = ?, glaucomaResult = 0, `analysisErrorCode` = ? WHERE id = ?;";
        $stmt2 = $this->mysqli->prepare($query2);
        $stmt2->bind_param("iii", $errorStatus, $errorCode, $imgID);
        $stmt2->execute();
    }
    
    public function rejectGlaucomaBorder($imgID, $errorValue){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query2 = "UPDATE `ret_image_info` SET glaucomaBorderRejected = ?, glaucomaResult = 0 WHERE id = ?;";
        $stmt2 = $this->mysqli->prepare($query2);
        $stmt2->bind_param("ii", $errorValue, $imgID);
        $stmt2->execute();
    }
    
    public function updateGlaucomaSegmentationError($imgID, $errorValue){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query2 = "UPDATE `ret_image_info` SET glaucomaSegmentationError = ?, glaucomaResult = 0 WHERE id = ?;";
        $stmt2 = $this->mysqli->prepare($query2);
        $stmt2->bind_param("ii", $errorValue, $imgID);
        $stmt2->execute();
    }
    
    public function updateLaterality($laterality, $imageID) {
        $query = 'UPDATE `ret_image_info` SET `region`=? WHERE `id`=?;';
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("si", $laterality, $imageID);
        $stmt->execute();
    }
    
    public function getGlaucomaResult($imageID) {
        $query = 'SELECT glaucomaResult FROM ret_image_info WHERE id = ?';
        return $this->performQueryGetResult($query, 'i', [$imageID]);
    }

    public function getUsersMail($id) {
        $query = 'SELECT email FROM `ret_users` WHERE userID = ?';
        return $this->performQueryGetResult($query, 'i', [$id]);
    }

    public function getDemoStatus($userid) {
        $query = 'SELECT * FROM `ret_users_demo` WHERE userID = ?';
        return $this->performQueryGetResult($query, 'i', [$userid]);
    }
    
    public function increaseDRandAMDUsed($userID) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = 'UPDATE ret_users_demo SET usedDRandAMD = usedDRandAMD + 1 WHERE userID = ?;';
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("i", $userID);
        $stmt->execute();
    }
    
    public function increaseGlaucomaUsed($userID) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = 'UPDATE ret_users_demo SET usedGlaucoma = usedGlaucoma + 1 WHERE userID = ?;';
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("i", $userID);
        $stmt->execute();
    }
    
    public function increaseOphthalUsed($userID) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = 'UPDATE ret_users_demo SET usedOphthal = usedOphthal + 1 WHERE userID = ?;';
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("i", $userID);
        $stmt->execute();
    }
    
    public function inputDemoPrepaidDetailsDRandAMD($imgID, $analysisType, $type) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = 'INSERT INTO `ret_demo_info` (`imgID`, `analysisType`, `type`) VALUES (?, ?, ?);';
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("iii", $imgID, $analysisType, $type);
        $stmt->execute();
    }

    ////////////////////////////////////////
    // Functions related to login, user info and chains
    ////////////////////////////////////////

    public function getPasswordLoginInfo($username) {
        $query = 'SELECT * FROM ret_users LEFT JOIN ret_user_settings ON ret_users.userID = ret_user_settings.userID WHERE username = ?';
        return $this->performQueryGetResult($query, 's', [$username]);
    }

    public function updateLastLogin($userID) {
        $mysqldatetime = DatabaseTime::getCurrentTime();
        $query = 'UPDATE `ret_users` SET `lastLoginTime` = ? WHERE `ret_users`.`userID` =  ?';
        return $this->performQueryGetResult($query, 'si', [$mysqldatetime, $userID]);
    }

    public function createUser($username, $password, $company, $contact, $tax_no, $address, $zipcode, $city, $phone, $country, $chainID, $refferal, $lang, $encryptionKey, $api, $hashed_secret, $encrypted_secret, $timezone, $camera, $enableGlaucoma, $lbRegionID) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->mysqli->autocommit(false);
            $mysqldatetime = DatabaseTime::getCurrentTime();
            $query1 = "INSERT INTO ret_users (username, password, email, typeID, chainID, encryptionKey, api, secret_encrypted, secret_hashed, time_created, lbRegion, s3usingUserID) VALUES (?,?,?,2,?,?,?,?,?,?,?,1)";
            $stmt1 = $this->mysqli->prepare($query1);
            $stmt1->bind_param("sssssssssi", $username, $password, $username, $chainID, $encryptionKey, $api, $encrypted_secret, $hashed_secret, $mysqldatetime, $lbRegionID);
            $stmt1->execute();

            $userid = $this->mysqli->insert_id;

            $query3 = "INSERT INTO ret_user_info (userID, companyName, contactpersonName, tax_no, address, zipcode, city, phone_no, country, language, timezone, camera) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt3 = $this->mysqli->prepare($query3);
            $stmt3->bind_param("sssssssssssi", $userid, $company, $contact, $tax_no, $address, $zipcode, $city, $phone, $country, $lang, $timezone, $camera);
            $stmt3->execute();
            
            if(!empty($this->config['site']) && $this->config['site'] == "optomed"){
                $query4 = "INSERT INTO ret_user_settings (userID, referral, AMDenabled, saveCustomerName) VALUES (?, ?, '0', '0')";
            }else{
                if($enableGlaucoma){
                    $query4 = "INSERT INTO ret_user_settings (userID, referral, GlaucomaEnabled) VALUES (?, ?, '1')";
                }else{
                    $query4 = "INSERT INTO ret_user_settings (userID, referral) VALUES (?, ?)";
                }
            }
            $stmt4 = $this->mysqli->prepare($query4);
            $stmt4->bind_param("is", $userid, $refferal);
            $stmt4->execute();
        } catch (\Exception $ex) {
            $this->mysqli->rollback();
            throw $ex;
        } finally {
            $this->mysqli->autocommit(true);
        }
        return $userid;
    }

    public function getUsersFromCountry($country) {
        $query = 'SELECT *, ret_users.userID AS userID FROM `ret_users` LEFT JOIN ret_user_info ON ret_users.userID = ret_user_info.userID WHERE ret_users.disabled = 0 AND ret_user_info.country =  ?';
        return $this->performQueryGetResult($query, 's', [$country]);
    }
    

    public function getUsersFromCountryOutsideChain($country) {
        $query = 'SELECT * FROM `ret_users` LEFT JOIN ret_user_info ON ret_users.userID = ret_user_info.userID WHERE ret_users.disabled = 0 AND ret_user_info.country = ? AND ret_users.chainID IS NULL';
        return $this->performQueryGetResult($query, 's', [$country]);
    }
    
    public function getDisabledUsersFromCountryOutsideChain($country) {
        $query = 'SELECT * FROM `ret_users` LEFT JOIN ret_user_info ON ret_users.userID = ret_user_info.userID WHERE ret_users.disabled = 1 AND ret_user_info.country = ? AND ret_users.chainID IS NULL';
        return $this->performQueryGetResult($query, 's', [$country]);
    }

    public function getUsersFromChain($chainID) {
        $query = 'SELECT * FROM `ret_users` LEFT JOIN ret_user_info ON ret_users.userID = ret_user_info.userID WHERE ret_users.disabled = 0 AND ret_users.chainID = ?';
        return $this->performQueryGetResult($query, 'i', [$chainID]);
    }
    
    public function getChainFromChainID($chainID){
        $query = 'SELECT * FROM ret_chains WHERE chain_id = ?';
        return $this->performQueryGetResult($query, 'i', [$chainID]);
    }
    
    public function getDisabledUsersFromChain($chainID) {
        $query = 'SELECT * FROM `ret_users` LEFT JOIN ret_user_info ON ret_users.userID = ret_user_info.userID WHERE ret_users.disabled = 1 AND ret_users.chainID = ?';
        return $this->performQueryGetResult($query, 'i', [$chainID]);
    }
    
    public function findUsers($searchTerm) {
        $wildcardSearchString = '%'. $searchTerm . '%';
        $query = 'SELECT * FROM `ret_users` LEFT JOIN ret_user_info ON ret_users.userID = ret_user_info.userID WHERE ret_users.disabled = 0 AND (ret_user_info.companyName LIKE ? OR ret_users.username LIKE ?)';
        return $this->performQueryGetResult($query, 'ss', [$wildcardSearchString,$wildcardSearchString]);
    }

    public function getUserInfo($username) {
        $query = 'SELECT * FROM `ret_users` WHERE username = ?';
        return $this->performQueryGetResult($query, 's', [$username]);
    }

    public function getUserInfoFromID($userID) {
        $query = 'SELECT * FROM `ret_users` WHERE userID = ?';
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }

    public function getUserInfoWithAdditionalInfo($username) {
        $query = 'SELECT *, ret_users.userID AS id  FROM `ret_users` LEFT JOIN ret_user_info ON ret_users.userID = ret_user_info.userID LEFT JOIN ret_user_settings ON ret_users.userID = ret_user_settings.userID WHERE username = ?';
        return $this->performQueryGetResult($query, 's', [$username]);
    }
    
    public function getUserInfoWithAdditionalInfoFromID($userID) {
        $query = 'SELECT *, ret_users.userID AS id  FROM `ret_users` LEFT JOIN ret_user_info ON ret_users.userID = ret_user_info.userID LEFT JOIN ret_user_settings ON ret_users.userID = ret_user_settings.userID WHERE ret_users.userID = ?';
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }

    public function getUserAdditionalInfoFromID($userID) {
        $query = 'SELECT * FROM `ret_user_info` WHERE userID = ?';
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    public function getUserSettingsFromID($userID) {
        $query = "SELECT * FROM `ret_user_settings` INNER JOIN ret_users ON ret_users.userID = ret_user_settings.userID WHERE ret_user_settings.userID = ?;";
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    public function getGlaucomaCameraIdentifierFromUserID($userID) {
        $query = "SELECT glaucomaCameraIdentifier FROM ret_user_info INNER JOIN ret_cameras ON ret_user_info.camera = ret_cameras.cameraID WHERE ret_user_info.userID = ?;";        
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    public function getGlaucomaCameraIdentifierFromImgID($imgID) {
        $query = "SELECT glaucomaCameraIdentifier FROM ret_image_info INNER JOIN ret_cameras ON ret_image_info.cameraID = ret_cameras.cameraID WHERE ret_image_info.id = ?;";        
        return $this->performQueryGetResult($query, 'i', [$imgID]);
    }

    public function updateAdditionalInfoOnUser($userID, $companyName, $contactPerson, $taxNo, $address, $zipcode, $city, $phone, $country, $lang, $timezone, $camera) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "INSERT INTO `ret_user_info` (userID, companyName, contactpersonName, tax_no, address, zipcode, city, country, phone_no, language, timezone, camera) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE companyName=VALUES(companyName), contactpersonName=VALUES(contactpersonName), tax_no=VALUES(tax_no), zipcode=VALUES(zipcode), address=VALUES(address), city=VALUES(city), country=VALUES(country), phone_no=VALUES(phone_no), language=VALUES(language), timezone=VALUES(timezone), camera=VALUES(camera)";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("sssssssssssi", $userID, $companyName, $contactPerson, $taxNo, $address, $zipcode, $city, $country, $phone, $lang, $timezone, $camera);
        $stmt->execute();
    }

    public function updateLangAndTimezoneOnUser($userID, $lang, $timezone) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "UPDATE `ret_user_info` SET `language` = ?, `timezone` = ? WHERE `ret_user_info`.`userID` = ?";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("ssi", $lang, $timezone, $userID);
        $stmt->execute();
    }
    
    public function updateTimezoneOnUser($userID, $timezone) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "UPDATE `ret_user_info` SET `timezone` = ? WHERE `ret_user_info`.`userID` = ?";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("si", $timezone, $userID);
        $stmt->execute();
    }

    public function updateEncryptedKeyOnUser($userID, $encryptedKey) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "UPDATE `ret_users` SET `encryptionKey` = ? WHERE `ret_users`.`userID` = ?";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("si", $encryptedKey, $userID);
        $stmt->execute();
    }

    public function updateSecretAndEncryptedKeyOnUser($userID, $secret, $encryptedKey) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "UPDATE `ret_users` SET `secret_encrypted` = ?, `encryptionKey` = ? WHERE `ret_users`.`userID` = ?";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("ssi", $secret, $encryptedKey, $userID);
        $stmt->execute();
    }

    public function updateSecretSecretHashedAndEncryptedKeyOnUser($userID, $secret, $secretHashed, $encryptedKey) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "UPDATE `ret_users` SET `secret_encrypted` = ?, `secret_hashed` = ?, `encryptionKey` = ? WHERE `ret_users`.`userID` = ?";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("sssi", $secret, $secretHashed, $encryptedKey, $userID);
        $stmt->execute();
    }

    public function updateSecretSecretHashedAndAPIOnUser($userID, $secret, $secretHashed, $api) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "UPDATE `ret_users` SET `secret_encrypted` = ?, `secret_hashed` = ?, `api` = ? WHERE `ret_users`.`userID` = ?";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("sssi", $secret, $secretHashed, $api, $userID);
        $stmt->execute();
    }

    public function getCurrentlyUsedCounties() {
        $query = 'SELECT `country` FROM ret_users INNER JOIN ret_user_info ON ret_user_info.userID=ret_users.userID WHERE ret_users.disabled = 0 GROUP BY country';
        return $this->performQueryGetResult($query, '', []);
    }

    public function getChains() {
        $query = 'SELECT chain_id, name FROM `ret_chains`';
        return $this->performQueryGetResult($query, '', []);
    }

    public function getCameras() {
        $query = 'SELECT * FROM `ret_cameras`';
        return $this->performQueryGetResult($query, '', []);
    }

    public function getChainNameFromID($chainID) {
        $query = "SELECT name FROM `ret_chains` WHERE chain_id = ?;";
        return $this->performQueryGetResult($query, 'i', [$chainID]);
    }

    public function getChainsFromCountry($country) {
        $query = "SELECT chainID, ret_chains.name FROM `ret_users` LEFT JOIN ret_user_info ON ret_users.userID = ret_user_info.userID INNER JOIN ret_chains ON ret_users.chainID = ret_chains.chain_id WHERE ret_user_info.country = ? AND disabled = 0 GROUP BY chainID;";
        return $this->performQueryGetResult($query, 's', [$country]);
    }

    public function getAllChains() {
        $query = 'SELECT * FROM `ret_chains`';
        return $this->performQueryGetResult($query, '', []);
    }

    public function updateChainForUser($userID, $chainID) {
        $query = 'UPDATE `ret_users` SET `chainID` = ? WHERE `ret_users`.`userID` = ?;';
        return $this->performQueryGetResult($query, 'ii', [$chainID, $userID]);
    }

    public function changePassword($userID, $password) {
        $query = "UPDATE ret_users SET password = ? WHERE userID = ?";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("si", $password, $userID);
        return $stmt->execute();
    }

    public function updateDRandAMDDemoStatusAndLimit($userID, $demoStatus, $limitNo, $expiryDate, $prepaidStatus, $daysWhenActivatedDRandAMD) {        
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "INSERT INTO `ret_users_demo` (userID, demoStatusDRandAMD, limitDRandAMD, dateTimeExpiresDRandAMD, prepaidStatusDRandAMD, daysWhenActivatedDRandAMD) VALUES(?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE demoStatusDRandAMD=VALUES(demoStatusDRandAMD), limitDRandAMD=VALUES(limitDRandAMD), dateTimeExpiresDRandAMD=VALUES(dateTimeExpiresDRandAMD), prepaidStatusDRandAMD=VALUES(prepaidStatusDRandAMD), daysWhenActivatedDRandAMD=VALUES(daysWhenActivatedDRandAMD)";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("isssii", $userID, $demoStatus, $limitNo, $expiryDate, $prepaidStatus, $daysWhenActivatedDRandAMD);
        $stmt->execute();
    }
    
    public function updateGlaucomaDemoStatusAndLimit($userID, $demoStatus, $limitNo, $expiryDate, $prepaidStatus, $daysWhenActivatedGlaucoma) {        
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "INSERT INTO `ret_users_demo` (userID, demoStatusGlaucoma, limitGlaucoma, dateTimeExpiresGlaucoma, prepaidStatusGlaucoma, daysWhenActivatedGlaucoma) VALUES(?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE demoStatusGlaucoma=VALUES(demoStatusGlaucoma), limitGlaucoma=VALUES(limitGlaucoma), dateTimeExpiresGlaucoma=VALUES(dateTimeExpiresGlaucoma), prepaidStatusGlaucoma=VALUES(prepaidStatusGlaucoma), daysWhenActivatedGlaucoma=VALUES(daysWhenActivatedGlaucoma)";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("isssii", $userID, $demoStatus, $limitNo, $expiryDate, $prepaidStatus, $daysWhenActivatedGlaucoma);
        $stmt->execute();        
    }
    
    public function updateOphthalDemoStatusAndLimit($userID, $demoStatus, $limitNo, $expiryDate, $prepaidStatus) {        
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "INSERT INTO `ret_users_demo` (userID, demoStatusOphthal, limitOphthal, dateTimeExpiresOphthal, prepaidStatusOphthal) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE demoStatusOphthal=VALUES(demoStatusOphthal), limitOphthal=VALUES(limitOphthal), dateTimeExpiresOphthal=VALUES(dateTimeExpiresOphthal), prepaidStatusOphthal=VALUES(prepaidStatusOphthal)";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("isssi", $userID, $demoStatus, $limitNo, $expiryDate, $prepaidStatus);
        $stmt->execute();        
    }

    public function getUserTypes() {
        $query = 'SELECT * FROM `ret_user_types`';
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function getUserRoles() {
        $query = 'SELECT * FROM `ret_user_roles`';
        return $this->performQueryGetResult($query, '', []);
    }

    public function updateUser($userID, $mail, $typeID, $disabled, $userRoleID) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "UPDATE `ret_users` SET `email` = ?, `typeID` = ?, `disabled` = ?, `roleID` = ? WHERE `ret_users`.`userID` = ?";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("siiii", $mail, $typeID, $disabled, $userRoleID, $userID);
        $stmt->execute();
    }
    
    public function updateUserSettings($userID, $AMDenabled, $referral, $referralMail, $saveName, $drOffset, $amdOffset, $onlyESB, $drEnabled, $glaucomaEnabled, $showCustomerNameInTables, $extendedGlaucomaInfoEnabled) {        
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "INSERT INTO `ret_user_settings` (userID, AMDenabled, drOffset, amdOffset, saveCustomerName, refferalMail, referral, onlyESB, DRenabled, GlaucomaEnabled, showCustomerNameInTables, extendedGlaucomaInfoEnabled) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE AMDenabled=VALUES(AMDenabled), drOffset=VALUES(drOffset), amdOffset=VALUES(amdOffset), saveCustomerName=VALUES(saveCustomerName), refferalMail=VALUES(refferalMail), referral=VALUES(referral) , onlyESB=VALUES(onlyESB), DRenabled=VALUES(DRenabled) , GlaucomaEnabled=VALUES(GlaucomaEnabled), showCustomerNameInTables=VALUES(showCustomerNameInTables), extendedGlaucomaInfoEnabled=VALUES(extendedGlaucomaInfoEnabled);";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("iiiiiiiiiiii", $userID, $AMDenabled, $drOffset, $amdOffset, $saveName, $referralMail, $referral, $onlyESB, $drEnabled, $glaucomaEnabled, $showCustomerNameInTables, $extendedGlaucomaInfoEnabled);
        $stmt->execute();
    }

    public function updateUserMail($userID, $contact_mail, $referral_mail) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->mysqli->autocommit(false);
            $queryUpdateUsers = "UPDATE `ret_users` SET `email` = ? WHERE `userID` = ?";
            $stmtUpdateUsers = $this->mysqli->prepare($queryUpdateUsers);
            $stmtUpdateUsers->bind_param("si", $contact_mail, $userID);
            $stmtUpdateUsers->execute();
            
            $queryUserSettings = "UPDATE `ret_user_settings` SET `refferalMail` = ? WHERE `userID` = ?";
            $stmtUserSettings = $this->mysqli->prepare($queryUserSettings);
            $stmtUserSettings->bind_param("si", $referral_mail, $userID);
            $stmtUserSettings->execute();
        } catch (\Exception $ex) {
            $this->mysqli->rollback();
            throw $ex;
        } finally {
            $this->mysqli->autocommit(true);
        }
    }

    ////////////////////////////////////////
    // Function related to picture upload
    ////////////////////////////////////////

    public function insertNotAnalyzedImg($filename, $cpr, $name, $userID, $failOnNameChange = false, $overwriteName = false , $region = NULL, $pid = NULL, $cameraID = NULL) {
        $mysqldate = DatabaseTime::getCurrentTime();

        $customerID = $this->createCustomer($cpr, $name, $userID, $failOnNameChange, $overwriteName);

        $query = "INSERT INTO `ret_image_info` (`id` ,`filename` ,`annotationsDR` ,`annotationsAMD` ,`userID` ,`timeUploaded` ,`timeAnalyzedDR` ,`timeAnalyzedAMD` ,`customerID` ,`region` ,`pid`, cameraID) VALUES (NULL , ?, NULL , NULL , ?, ?, NULL , NULL , ?, ?, ?, ?);";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("ssssssi", $filename, $userID, $mysqldate, $customerID, $region, $pid, $cameraID);
        $stmt->execute();
        return $this->mysqli->insert_id;
    }
    
    public function insertNotAnalyzedImgWithoutCustomer($filename, $userID, $pid, $region, $camerID = NULL) {
        $mysqldate = DatabaseTime::getCurrentTime();

        $query = "INSERT INTO `ret_image_info` (`id` ,`filename` ,`annotationsDR` ,`annotationsAMD` ,`userID` ,`timeUploaded` ,`timeAnalyzedDR` ,`timeAnalyzedAMD` ,`customerID` ,`region` ,`pid`, cameraID) VALUES (NULL , ?, NULL , NULL , ?, ?, NULL , NULL , NULL, ?, ?, ?);";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("sssssi", $filename, $userID, $mysqldate, $region, $pid, $camerID);
        $stmt->execute();
        return $this->mysqli->insert_id;
    }
    
    public function createCustomer($cpr, $name, $userID, $failOnNameChange = false, $overwriteName = false) {
        $querySelect = 'SELECT * FROM ret_customers INNER JOIN ret_users_customers ON ret_customers.customerID = ret_users_customers.customerID WHERE socialsecurity_no = ? AND ret_users_customers.userID = ?;';
        $customer = $this->performQueryGetResult($querySelect, 'si', [$cpr, $userID]);
        if ($customer->num_rows == 0) {
            $queryCreateCustomer = "INSERT INTO `ret_customers` (`customerID` ,`name` ,`socialsecurity_no`) VALUES (NULL , ?, ?)";
            $stmtCreateCustomer = $this->mysqli->prepare($queryCreateCustomer);
            $stmtCreateCustomer->bind_param("ss", $name, $cpr);
            $stmtCreateCustomer->execute();
            $customerID = $this->mysqli->insert_id;

            $query = "INSERT INTO `ret_users_customers` (`userID`, `customerID`) VALUES (?, ?);";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("ss", $userID, $customerID);
            $stmt->execute();
        } else {
            $customerRow = $customer->fetch_array();
            if($overwriteName === true){
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                try {
                    $this->mysqli->autocommit(false);
                    $dbTime = DatabaseTime::getCurrentTime();
                    $historyQuery = 'INSERT INTO `ret_customers_name_change_history` (`customerID`, `oldName`, `time`) VALUES (?, ?, ?);';
                    $historyStmt = $this->mysqli->prepare($historyQuery);
                    $historyStmt->bind_param("iss", $customerRow['customerID'], $customerRow['name'], $dbTime);
                    $historyStmt->execute();
                    
                    $updateNameQuery = "UPDATE `ret_customers` SET `name`=? WHERE `customerID`=?;";
                    $updateNameStmt = $this->mysqli->prepare($updateNameQuery);
                    $updateNameStmt->bind_param("ss", $name, $customerRow['customerID']);
                    $updateNameStmt->execute();
                } catch (\Exception $ex) {
                    $this->mysqli->rollback();
                    throw $ex;
                } finally {
                    $this->mysqli->autocommit(true);
                }
            }else if($failOnNameChange === true && $name != $customerRow["name"]){
                $ex = new NameChangeDetected("The name inputted does not equal the existing name");
                $ex->setOldName($customerRow["name"]);
                throw $ex;
            }
            $customerID = $customerRow["customerID"];
        }
        return $customerID;
    }
    
    public function updateCustomerIDOnImg($customerID, $imgID){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        try {
            $query = "UPDATE `ret_image_info` SET `customerID`=? WHERE `id`=?";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("ss", $customerID, $imgID);
            $stmt->execute();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function removeNotAnalyzedImg($id) {
        $query = 'DELETE FROM `ret_image_info` WHERE `ret_image_info`.`id` = ?;';
        return $this->performQueryGetResult($query, 'i', [$id]);
    }

    public function removeNotAnalyzedImgWithUserID($id, $userID) {
        $query = 'DELETE FROM `ret_image_info` WHERE `ret_image_info`.`id` = ? AND `ret_image_info`.`userID` = ? AND `annotationsDR` IS NULL AND `annotationsAMD` IS NULL AND `refID` IS NULL';
        return $this->performQueryGetResult($query, 'ii', [$id, $userID]);
    }

    ////////////////////////////////////////
    // Functions related to analyzing picture
    ////////////////////////////////////////

    public function getNotAnalyzedImg($id) {
        $query = 'SELECT username, ret_users.userID as userID, ret_users.s3usingUserID as s3usingUserID, runningAnalysisDR, runningAnalysisAMD, annotationsDR, annotationsAMD, cameraID, camera FROM ret_image_info INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID INNER JOIN ret_user_info ON ret_image_info.userID = ret_user_info.userID WHERE id = ?;';
        return $this->performQueryGetResult($query, 'i', [$id]);
    }
    
    public function getAllImgsInRange($startID, $endID) {
        $query = 'SELECT * FROM ret_image_info INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID WHERE id >= ? AND id <= ?;';
        return $this->performQueryGetResult($query, 'ii', [$startID, $endID]);
    }

    public function updateRunningAnalysis($id, $type) {
        if($type == 0){
            $query = 'UPDATE `ret_image_info` SET `runningAnalysisDR` = "1" WHERE `ret_image_info`.`id` = ?;';
        } else if($type == 1){
            $query = 'UPDATE `ret_image_info` SET `runningAnalysisAMD` = "1" WHERE `ret_image_info`.`id` = ?;';
        }
        return $this->performQueryGetResult($query, 'i', [$id]);
    }

    public function updateNotRunningAnalysis($id, $type) {
        if($type == 0){
            $query = 'UPDATE `ret_image_info` SET `runningAnalysisDR` = "0" WHERE `ret_image_info`.`id` = ?;';
        } else if($type == 1){
            $query = 'UPDATE `ret_image_info` SET `runningAnalysisAMD` = "0" WHERE `ret_image_info`.`id` = ?;';
        }
        return $this->performQueryGetResult($query, 'i', [$id]);
    }
    
    public function updateCameraOnImageInfo($imgID, $cameraID) {
        $query = "UPDATE `ret_image_info` SET `cameraID` = ? WHERE `id` = ?;";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("ii", $cameraID, $imgID);
        $stmt->execute();
    }

    ////////////////////////////////////////
    // Functions related to getting picture information
    ////////////////////////////////////////

    public function getNotAnalyzedImgInfo($userID) {
        $query = "SELECT * FROM `ret_image_info` INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID LEFT JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID WHERE ret_image_info.userID = ? AND `annotationsDR` IS NULL AND `annotationsAMD` IS NULL AND `glaucomaResult` IS NULL AND ret_image_info.refID IS NULL ORDER BY timeUploaded DESC";
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }

    public function getAnalyzedImgInfo($userid) {
        $query = "SELECT * FROM ret_image_info INNER JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID WHERE userID = ? AND (`annotationsDR` IS NOT NULL OR `annotationsAMD` IS NOT NULL OR `glaucomaResult` IS NOT NULL OR status IS NOT NULL) ORDER BY GREATEST(COALESCE(timeAnalyzedDR, 0), COALESCE(timeAnalyzedAMD, 0), COALESCE(timeUploaded, 0), COALESCE(timeGlaucomaStarted, 0)) DESC";
        return $this->performQueryGetResult($query, 'i', [$userid]);
    }
    
    public function getAnalyzedImgInfoPaginated($userid, $limit, $offset) {
        $query = "SELECT * FROM ret_image_info INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID INNER JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID  WHERE ret_image_info.userID = ? AND (`annotationsDR` IS NOT NULL OR `annotationsAMD` IS NOT NULL OR `glaucomaResult` IS NOT NULL OR status IS NOT NULL) ORDER BY GREATEST(COALESCE(timeAnalyzedDR, 0), COALESCE(timeAnalyzedAMD, 0), COALESCE(timeUploaded, 0), COALESCE(timeGlaucomaStarted, 0)) DESC LIMIT ? OFFSET ?;";
        return $this->performQueryGetResult($query, 'iii', [$userid, $limit, $offset]);
    }
    
    public function getAnalyzedImgInfoCount($userid) {
        $query = "SELECT COUNT(*) AS COUNT FROM ret_image_info LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID  WHERE userID = ? AND (`annotationsDR` IS NOT NULL OR `annotationsAMD` IS NOT NULL OR `glaucomaResult` IS NOT NULL OR status IS NOT NULL) ORDER BY GREATEST(COALESCE(timeAnalyzedDR, 0), COALESCE(timeAnalyzedAMD, 0), COALESCE(timeUploaded, 0), COALESCE(timeGlaucomaStarted, 0)) DESC";
        return $this->performQueryGetResult($query, 'i', [$userid]);
    }

    public function getAnalyzedImgInfoSearch($userid, $searchstring) {
        $wildcardSearchString = '%'. $searchstring . '%';
        $query = "SELECT * FROM ret_image_info INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID INNER JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID  WHERE ret_image_info.userID = ? AND (`annotationsDR` IS NOT NULL OR `annotationsAMD` IS NOT NULL  OR status IS NOT NULL OR glaucomaResult IS NOT NULL) AND (ret_customers.name LIKE ? OR ret_customers.socialsecurity_no LIKE ?) ORDER BY GREATEST(COALESCE(timeAnalyzedDR, 0), COALESCE(timeAnalyzedAMD, 0), COALESCE(timeGlaucomaStarted, 0)) DESC";
        return $this->performQueryGetResult($query, 'iss', [$userid, $wildcardSearchString, $wildcardSearchString]);
    }
    
    public function getAnalyzedImgInfoSearchInUsers($searchstring, $usersqllist) {
        $wildcardSearchString = '%'. $searchstring . '%';
        if($usersqllist === 'ALL'){
            $query = "SELECT * FROM ret_image_info INNER JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID  WHERE (`annotationsDR` IS NOT NULL OR `annotationsAMD` IS NOT NULL OR status IS NOT NULL OR glaucomaResult IS NOT NULL) AND (ret_customers.name LIKE ? OR ret_customers.socialsecurity_no LIKE ?) ORDER BY GREATEST(COALESCE(timeAnalyzedDR, 0), COALESCE(timeAnalyzedAMD, 0), COALESCE(timeGlaucomaStarted, 0)) DESC";
            return $this->performQueryGetResult($query, 'ss', [$wildcardSearchString, $wildcardSearchString]);
        }else{
            $query = "SELECT * FROM ret_image_info INNER JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID  WHERE ret_image_info.userID IN (" . $this->mysqli->real_escape_string($usersqllist) . ") AND (`annotationsDR` IS NOT NULL OR `annotationsAMD` IS NOT NULL OR status IS NOT NULL OR glaucomaResult IS NOT NULL) AND (ret_customers.name LIKE ? OR ret_customers.socialsecurity_no LIKE ?) ORDER BY GREATEST(COALESCE(timeAnalyzedDR, 0), COALESCE(timeAnalyzedAMD, 0), COALESCE(timeGlaucomaStarted, 0)) DESC";        
            return $this->performQueryGetResult($query, 'ss', [$wildcardSearchString, $wildcardSearchString]);
            
        }
    }

    public function getAnalyzedImgInfoLast7Days($userid) {
        $query = "SELECT * FROM ret_image_info INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID INNER JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID WHERE ret_image_info.userID = ? AND (timeUploaded >= ( CURDATE() - INTERVAL 7 DAY ) OR timeAnalyzedDR >= ( CURDATE() - INTERVAL 7 DAY ) OR timeAnalyzedAMD >= ( CURDATE() - INTERVAL 7 DAY ) OR timeGlaucomaStarted >= ( CURDATE() - INTERVAL 7 DAY )) AND (`annotationsDR` IS NOT NULL OR `annotationsAMD` IS NOT NULL OR `glaucomaResult` IS NOT NULL OR status IS NOT NULL) ORDER BY GREATEST(COALESCE(timeAnalyzedDR, 0), COALESCE(timeAnalyzedAMD, 0), COALESCE(timeUploaded, 0), COALESCE(timeGlaucomaStarted, 0)) DESC";
        return $this->performQueryGetResult($query, 'i', [$userid]);
    }

    public function getSingleAnalyzedImgInfoWithUsername($id) {
        $query = "SELECT * FROM ret_image_info INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID INNER JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID WHERE id = ?";
        return $this->performQueryGetResult($query, 'i', [$id]);
    }
    
    public function getSingleAnalyzedImgInfoWithUsernameWithoutCustomerInfo($id) {
        $query = "SELECT * FROM ret_image_info INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID WHERE id = ?";
        return $this->performQueryGetResult($query, 'i', [$id]);
    }

    public function getSingleAnalyzedImgInfoWithRef($id) {
        $query = "SELECT * FROM ret_image_info LEFT JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID WHERE id = ? ORDER BY timeAnalyzedDR DESC;";
        return $this->performQueryGetResult($query, 'i', [$id]);
    }

    public function getSingleAnalyzedImgInfoWithUsernameAndRef($id) {
        $query = "SELECT * FROM ret_image_info INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID LEFT JOIN ret_user_info ON ret_image_info.userID = ret_user_info.userID LEFT JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID WHERE id = ?;";
        return $this->performQueryGetResult($query, 'i', [$id]);
    }
    
    public function getSingleAnalyzedImgInfoWithUsernameAndGlaucoma($id) {
        $query = "SELECT * FROM ret_image_info INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID LEFT JOIN ret_user_info ON ret_image_info.userID = ret_user_info.userID LEFT JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID WHERE id = ?;";
        return $this->performQueryGetResult($query, 'i', [$id]);
    }
    
    public function getAnnDRData($id){
        $query = "SELECT annDRData FROM `ret_image_info` WHERE id = ?;";
        return $this->performQueryGetResult($query, 'i', [$id]);
    }
    
    public function getAnnAMDData($id){
        $query = "SELECT annAMDData FROM `ret_image_info` WHERE id =  ?;";
        return $this->performQueryGetResult($query, 'i', [$id]);
    }

    ////////////////////////////////////////
    // Functions related to Statistics
    ////////////////////////////////////////

    public function getStatisticsDataAllForUser($userID) {
        $query = "SELECT ret_image_info.userID AS userID, timeUploaded, refTime, timeAnalyzedDR, timeAnalyzedAMD, quality, annotationsDR, annotationsAMD, ret_image_info.refID AS refID, answerTypeDR, answerTypeAMD, status, timeGlaucomaStarted, glaucomaResult, isSaturated, glaucomaAnalysisError, glaucomaSegmentationError, glaucomaBorderRejected, answerTypeGlaucoma FROM ret_image_info LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID WHERE userID = ? ORDER BY timeUploaded ASC;";
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    public function getStatisticsDataAllForUserThisYear($userID) {
        $query = "SELECT ret_image_info.userID AS userID, timeUploaded, refTime, timeAnalyzedDR, timeAnalyzedAMD, quality, annotationsDR, annotationsAMD, ret_image_info.refID AS refID, answerTypeDR, answerTypeAMD, status, timeGlaucomaStarted, glaucomaResult, isSaturated, glaucomaAnalysisError, glaucomaSegmentationError, glaucomaBorderRejected, answerTypeGlaucoma FROM ret_image_info LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID WHERE userID = ? AND timeUploaded BETWEEN CONCAT(YEAR(CURDATE())-1,'-01-01') AND CONCAT(YEAR(CURDATE())+1,'-12-31')  ORDER BY timeUploaded ASC;";
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    public function getStatisticsDataAllForCountryThisYear($country) {
        $query = "SELECT ret_image_info.userID AS userID, timeUploaded, refTime, timeAnalyzedDR, timeAnalyzedAMD, quality, annotationsDR, annotationsAMD, ret_image_info.refID AS refID, answerTypeDR, answerTypeAMD, status, timeGlaucomaStarted, glaucomaResult, isSaturated, glaucomaAnalysisError, glaucomaSegmentationError, glaucomaBorderRejected, answerTypeGlaucoma FROM ret_image_info INNER JOIN ret_user_info ON ret_user_info.userID = ret_image_info.userID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID WHERE country = ? AND timeUploaded BETWEEN CONCAT(YEAR(CURDATE())-1,'-01-01') AND CONCAT(YEAR(CURDATE())+1,'-12-31')  ORDER BY timeUploaded ASC;";
        return $this->performQueryGetResult($query, 's', [$country]);
    }
    
    public function getUserCreationStatisticsForCountry($country) {
        $query = "SELECT time_created FROM ret_users INNER JOIN ret_user_info ON ret_user_info.userID = ret_users.userID WHERE country = ? AND disabled = 0;";
        return $this->performQueryGetResult($query, 's', [$country]);
    }
    
    public function getUserCreationStatisticsForCountryWithinMonth($country) {
        $query = "SELECT time_created FROM ret_users INNER JOIN ret_user_info ON ret_user_info.userID = ret_users.userID WHERE country = ? AND YEAR(time_created) = YEAR(NOW())AND MONTH(time_created) = MONTH(NOW()) AND disabled = 0;";
        return $this->performQueryGetResult($query, 's', [$country]);
    }
    
    public function getStatisticsDataAllForChainThisYear($chainID) {
        $query = "SELECT ret_image_info.userID AS userID, timeUploaded, refTime, timeAnalyzedDR, timeAnalyzedAMD, quality, annotationsDR, annotationsAMD, ret_image_info.refID AS refID, answerTypeDR, answerTypeAMD, status, timeGlaucomaStarted, glaucomaResult, isSaturated, glaucomaAnalysisError, glaucomaSegmentationError, glaucomaBorderRejected, answerTypeGlaucoma FROM ret_image_info INNER JOIN ret_users ON ret_users.userID = ret_image_info.userID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID WHERE chainID = ? AND timeUploaded BETWEEN CONCAT(YEAR(CURDATE())-1,'-01-01') AND CONCAT(YEAR(CURDATE())+1,'-12-31')  ORDER BY timeUploaded ASC;";
        return $this->performQueryGetResult($query, 'i', [$chainID]);
    }
    
    public function getUserCreationStatisticsForChain($chainID) {
        $query = "SELECT time_created FROM ret_users WHERE chainID = ? AND disabled = 0;";
        return $this->performQueryGetResult($query, 'i', [$chainID]);
    }
    
    public function getUserCreationStatisticsForChainWithinMonth($chainID) {
        $query = "SELECT time_created FROM ret_users WHERE chainID = ? AND YEAR(time_created) = YEAR(NOW())AND MONTH(time_created) = MONTH(NOW()) AND disabled = 0;";
        return $this->performQueryGetResult($query, 'i', [$chainID]);
    }
    
    public function getStatisticsDataAllForGlaucoma() {
        $query = "SELECT id AS imgID, ret_image_info.userID AS userID, timeGlaucomaStarted, glaucomaResult, apiID, ret_user_roles.description AS roleDescription, ret_demo_info.type AS demoType FROM ret_image_info LEFT JOIN ret_glaucoma_info ON ret_glaucoma_info.glaucomaID = ret_image_info.glaucomaID LEFT JOIN ret_users ON ret_users.userID = ret_image_info.userID LEFT JOIN ret_user_roles ON ret_user_roles.userRoleID = ret_users.roleID LEFT JOIN ret_demo_info ON ret_demo_info.imgID = ret_image_info.id WHERE ret_image_info.glaucomaID IS NOT NULL AND ret_image_info.glaucomaResult IS NOT NULL AND ret_image_info.glaucomaResult != 0 AND (ret_demo_info.analysisType = 3 OR ret_demo_info.analysisType IS NULL) ORDER BY apiID ASC";
        return $this->performQueryGetResult($query, '', []);
    }

    ////////////////////////////////////////
    // Functions related to Referral
    ////////////////////////////////////////

    public function initializeRefferal($id, $cpr, $pressure, $comment, $refDetailID) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->mysqli->autocommit(false);
            $mysqldate = DatabaseTime::getCurrentTime();

            $query = "INSERT INTO `ret_referral` (`refID` ,`refTime` ,`cpr` ,`status` ,`answerTypeDR`, `answerTypeAMD` ,`answerComment`, `eyespecialistID`, `pressure`, `optometristComment`, `detailsID`)VALUES (NULL ,?, ?, '0', NULL , NULL , NULL, NULL, ?, ?, ?);";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("ssssi", $mysqldate, $cpr, $pressure, $comment, $refDetailID);
            $stmt->execute();

            $refID = $this->mysqli->insert_id;
            
            $query2 = "UPDATE `ret_image_info` SET refID = ? WHERE id = ?;";
            $stmt2 = $this->mysqli->prepare($query2);
            $stmt2->bind_param("ii", $refID, $id);
            $stmt2->execute();
        } catch (\Exception $ex) {
            $this->mysqli->rollback();
            throw $ex;
        } finally {
            $this->mysqli->autocommit(true);
        }
        
    }

    public function createDetailsOfRef($age, $visus_os, $last_oph_consult, $amsler, $visus_od, $next_oph_consult, $diagnosed_diabetes, $duration_diabetes, $tension, $cct_tension, $glaucoma_family, $reason, $visus_before_os, $which_sympton, $visus_before_od, $position_noticed, $change_prescription_period, $comment, $eyePrescriptionBeforeOS, $eyePrescriptionBeforeOD, $eyePrescriptionAfterOS, $eyePrescriptionAfterOD) {
        $query = "INSERT INTO `ret_refferal_details` (`refDetailsID`, `age`, `amslerTest`, `currentVisusOS`, `currentVisusOD`, `lastConsultation`, `nextConsultation`, `diagnosedDiabetes`, `durationDiabetes`, `tension`, `tensionCCT`, `familyGlaucoma`, `reason`, `symptom`, `visusBeforeOS`, `visusBeforeOD`, `periodChange`, `eyePrescriptionBeforeOS`, `eyePrescriptionBeforeOD`, `eyePrescriptionAfterOS`, `eyePrescriptionAfterOD`, `position`, `comment`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("iissiiiisiiiississssis", $age, $amsler, $visus_os, $visus_od, $last_oph_consult, $next_oph_consult, $diagnosed_diabetes, $duration_diabetes, $tension, $cct_tension, $glaucoma_family, $reason, $which_sympton, $visus_before_os, $visus_before_od, $change_prescription_period, $eyePrescriptionBeforeOS, $eyePrescriptionBeforeOD, $eyePrescriptionAfterOS, $eyePrescriptionAfterOD, $position_noticed, $comment);
        $stmt->execute();
        return $this->mysqli->insert_id;
    }

    public function getReferralDetails($refDetailID) {
        $query = "SELECT * FROM `ret_refferal_details` WHERE refDetailsID = ?;";
        return $this->performQueryGetResult($query, 'i', [$refDetailID]);
    }
    
    public function getReferralAwaitingResponseGESB() {
        $query = "SELECT * FROM `ret_referral` INNER JOIN ret_image_info ON ret_image_info.refID = ret_referral.refID INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID INNER JOIN ret_customers ON ret_image_info.customerID = ret_customers.customerID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID WHERE `status` = 0 AND lbRegion IS NULL ORDER BY `ret_referral`.`refID` ASC;";
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function getReferralAwaitingResponseLESB($lbregion) {
        $query = "SELECT * FROM `ret_referral` INNER JOIN ret_image_info ON ret_image_info.refID = ret_referral.refID INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID INNER JOIN ret_customers ON ret_image_info.customerID = ret_customers.customerID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID WHERE `status` = 0 AND lbRegion = ? ORDER BY `ret_referral`.`refID` ASC;";
        return $this->performQueryGetResult($query, 'i', [$lbregion]);
    }
    
    public function getReferralWithResponseGESBCount() {
        $query = "SELECT COUNT(*) AS COUNT FROM `ret_referral` INNER JOIN ret_image_info ON ret_image_info.refID = ret_referral.refID INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID INNER JOIN ret_customers ON ret_image_info.customerID = ret_customers.customerID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID  WHERE `status` = 1 AND lbRegion IS NULL;";
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function getReferralWithResponseGESBPaginated($limit, $offset) {
        $query = "SELECT * FROM `ret_referral` INNER JOIN ret_image_info ON ret_image_info.refID = ret_referral.refID INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID INNER JOIN ret_customers ON ret_image_info.customerID = ret_customers.customerID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID  WHERE `status` = 1 AND lbRegion IS NULL ORDER BY `ret_referral`.`refID` DESC LIMIT ? OFFSET ?;";
        return $this->performQueryGetResult($query, 'ii', [$limit, $offset]);
    }    
           
    public function getReferralWithResponseLESBCount($lbregion) {
        $query = "SELECT COUNT(*) AS COUNT FROM `ret_referral` INNER JOIN ret_image_info ON ret_image_info.refID = ret_referral.refID INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID INNER JOIN ret_customers ON ret_image_info.customerID = ret_customers.customerID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID  WHERE `status` = 1 AND lbRegion = ? ORDER BY `ret_referral`.`refID` DESC;";
        return $this->performQueryGetResult($query, 'i', [$lbregion]);
    }
    
    public function getReferralWithResponseLESBPaginated($lbregion, $limit, $offset) {
        $query = "SELECT * FROM `ret_referral` INNER JOIN ret_image_info ON ret_image_info.refID = ret_referral.refID INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID INNER JOIN ret_customers ON ret_image_info.customerID = ret_customers.customerID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID  WHERE `status` = 1 AND lbRegion = ? ORDER BY `ret_referral`.`refID` DESC LIMIT ? OFFSET ?;";
        return $this->performQueryGetResult($query, 'iii', [$lbregion, $limit, $offset]);
    }

    public function responseToReferral($refID, $answerTypeDR, $answerTypeAMD, $comment, $eyespecialistID, $answerTypeGlaucoma) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = 'UPDATE `ret_referral` SET `status` = 1, `answerTypeDR` = ?, `answerTypeAMD` = ?, `answerComment` = ?, `eyespecialistID` = ?, `answerTime` = ?, answerTypeGlaucoma = ? WHERE `ret_referral`.`refID` = ?;';
        $mysqldate = DatabaseTime::getCurrentTime();
        
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("iisisii", $answerTypeDR, $answerTypeAMD, $comment, $eyespecialistID, $mysqldate, $answerTypeGlaucoma, $refID);
        $stmt->execute();
    }

    public function getUserAndImageDetailsFromRefID($refID) {
        $sql = "SELECT ret_users.email, ret_user_settings.refferalMail, ret_users.userID, ret_image_info.id FROM `ret_referral` LEFT JOIN ret_image_info ON ret_referral.refID = ret_image_info.refID LEFT JOIN ret_users ON ret_image_info.userID = ret_users.userID LEFT JOIN ret_user_settings ON ret_user_settings.userID = ret_users.userID WHERE ret_referral.refID = ?";
        return $this->performQueryGetResult($sql, 'i', [$refID]);
    }

    public function getAllAnswers() {
        $query = "SELECT ret_refferal_found_answers.foundID AS foundID, ret_refferal_found_options.text_for_opthal AS optionText, ret_refferal_found_answers.text_for_opthal AS answerText, ret_refferal_found_options.foundOptionID AS optionID FROM `ret_refferal_found_answers` LEFT JOIN ret_refferal_found_options ON ret_refferal_found_answers.foundID = ret_refferal_found_options.foundID ORDER BY ret_refferal_found_answers.foundID ASC;";
        return $this->performQueryGetResult($query, '', []);
    }

    public function getAllPositions() {
        $query = "SELECT * FROM `ret_refferal_positions`;";
        return $this->performQueryGetResult($query, '', []);
    }

    public function getAllNotifications() {
        $query = "SELECT * FROM `ret_refferal_notifications`;";
        return $this->performQueryGetResult($query, '', []);
    }

    public function getAllRecommendations() {
        $query = "SELECT * FROM `ret_refferal_recommendation`;";
        return $this->performQueryGetResult($query, '', []);
    }

    public function getFirstNotCorrectResponded() {
        $query = "SELECT id  FROM `ret_referral` INNER JOIN ret_image_info ON ret_referral.refID = ret_image_info.refID WHERE `recomID` IS NULL AND `notificationID` IS NULL AND status = 1 ORDER BY ret_referral.refID ASC LIMIT 1;";
        return $this->performQueryGetResult($query, '', []);
    }

    public function updatePosition($positionID, $refID) {
        $query = "UPDATE `ret_referral` SET `positionID` = ? WHERE `ret_referral`.`refID` = ?;";
        return $this->performQueryGetResult($query, 'ii', [$positionID, $refID]);
    }

    public function updateNotification($notificationID, $refID) {
        $query = "UPDATE `ret_referral` SET `notificationID` = ? WHERE `ret_referral`.`refID` = ?;";
        return $this->performQueryGetResult($query, 'ii', [$notificationID, $refID]);
    }

    public function updateRecommendation($recomID, $refID) {
        $query = "UPDATE `ret_referral` SET `recomID` = ? WHERE `ret_referral`.`refID` = ?;";
        return $this->performQueryGetResult($query, 'ii', [$recomID, $refID]);
    }

    public function setAnswerOnRefferal($answerID, $refID) {
        $query = "INSERT INTO `ret_refferal_found_relation` (`refID`, `foundID`) VALUES (?, ?);";
        return $this->performQueryGetResult($query, 'ii', [$refID, $answerID]);
    }

    public function setOptionOnRefferal($optionID, $refID) {
        $query = "INSERT INTO `ret_refferal_found_options_relation` (`refID`, `foundOptionID`) VALUES (?, ?);";
        return $this->performQueryGetResult($query, 'ii', [$refID, $optionID]);
    }

    public function getOphthalmologistReportFromDate($date, $eyespecialistID) {
        $wildcardDate = $date . '%';
        $query = "SELECT socialsecurity_no, answerComment  FROM `ret_referral` INNER JOIN ret_image_info ON ret_referral.refID = ret_image_info.refID INNER JOIN ret_customers ON ret_image_info.customerID = ret_customers.customerID WHERE `answerTime` LIKE ? AND eyespecialistID = ? ORDER BY ret_referral.refID ASC;";
        return $this->performQueryGetResult($query, 'si', [$wildcardDate, $eyespecialistID]);
    }

    ////////////////////////////////////////
    // Functions related to Local eye specialist backup
    ////////////////////////////////////////
    
    public function getLBRegions() {
        $query = "SELECT * FROM `ret_lb_regions` ORDER BY `ret_lb_regions`.`regID` ASC;";
        return $this->performQueryGetResult($query, '', []);
    }

    public function updateLBRegionForUser($userID, $lbRegionID) {
        $query = "UPDATE `ret_users` SET `lbRegion` = ? WHERE `ret_users`.`userID` = ?;";
        return $this->performQueryGetResult($query, 'ii', [$lbRegionID, $userID]);
    }

    public function createLBRegion($title, $country) {
        $query = "INSERT INTO `ret_lb_regions` (`regID`, `title`, `country`) VALUES (NULL, ?, ?);";
        $this->performQueryGetResult($query, 'ss', [$title, $country]);
    }

    ////////////////////////////////////////
    // Functions related to api csv export
    ////////////////////////////////////////

    public function getAPISettingsForUser($userid) {
        $query = "SELECT * FROM `ret_api_user_settings`  WHERE userID = ?;";
        return $this->performQueryGetResult($query, 'i', [$userid]);
    }

    public function setCSVFirstRunForUser($userid, $firstRunStatus) {
        $query = "UPDATE `ret_api_user_settings` SET `CSVFirstRun` = ? WHERE `ret_api_user_settings`.`userID` = ?;";
        return $this->performQueryGetResult($query, 'ii', [$firstRunStatus, $userid]);
    }

    public function getDataForCSV($userid) {
        $query = "SELECT * FROM `ret_api_csv_missing` INNER JOIN ret_image_info ON ret_api_csv_missing.imgID = ret_image_info.id INNER JOIN ret_customers ON ret_image_info.customerID = ret_customers.customerID WHERE ret_api_csv_missing.userID = ?;";
        return $this->performQueryGetResult($query, 'i', [$userid]);
    }

    public function getSpecificRowInCSVMissingTable($userid, $imgID) {
        $query = "SELECT * FROM `ret_api_csv_missing` WHERE userID = ? AND imgID = ?;";
        return $this->performQueryGetResult($query, 'ii', [$userid, $imgID]);
    }

    public function cleanCSVMissingTableForUser($userid) {
        $query = "DELETE FROM `ret_api_csv_missing` WHERE `userID` = ?;";
        return $this->performQueryGetResult($query, 'i', [$userid]);
    }
    
    public function deleteCSVMissingForUserAndImgID($userid, $imgID) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "DELETE FROM `ret_api_csv_missing` WHERE `userID` = ? AND imgID = ?;";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("ii", $userid, $imgID);
        $stmt->execute();
        return true;
    }

    public function insertIDToCSVMissingTable($userid, $imgID) {
        $query = "INSERT INTO `ret_api_csv_missing` (`userID`, `imgID`) VALUES (?, ?);";
        return $this->performQueryGetResult($query, 'ii', [$userid, $imgID]);
    }

    public function insertCSVHistory($userid, $data) {
        $phpdate = new \DateTime();
        $mysqldate = $phpdate->format('Y-m-d H:i:s');
        
        $query = "INSERT INTO `ret_api_csv_history` (`histortyID`, `userID`, `imgIDs`, `time`) VALUES (NULL, ?, ?, ?);";
        return $this->performQueryGetResult($query, 'iss', [$userid, $data, $mysqldate]);
    }

    ////////////////////////////////////////
    // Functions related to chain
    ////////////////////////////////////////

    public function createChain($title) {
        $query = "INSERT INTO `ret_chains` (`chain_id`, `name`) VALUES (NULL, ?);";
        return $this->performQueryGetResult($query, 's', [$title]);
    }

    public function getChainAndPDFInfo($chainID) {
        $query = "SELECT * FROM `ret_chains` LEFT JOIN ret_pdf_settings_chains ON ret_chains.chain_id = ret_pdf_settings_chains.chainID WHERE `chain_id` = ?;";
        return $this->performQueryGetResult($query, 'i', [$chainID]);
    }

    public function getPDFSettinsInfo($chainID) {
        $query = "SELECT * FROM `ret_pdf_settings_chains` WHERE `chainID` = ?;";
        return $this->performQueryGetResult($query, 'i', [$chainID]);
    }

    public function getPDFSettinsInfoFromPDFSettingsID($pdfSettingsID) {
        $query = "SELECT * FROM `ret_pdf_settings_chains` WHERE `pdfChainID` = ?;";
        return $this->performQueryGetResult($query, 'i', [$pdfSettingsID]);
    }

    public function updateChainInfo($chainID, $chainName, $adminAccess, $chainUsersAccess, $separateLists, $showSavePDFOption, $hidePDF, $hideDownloadImageWithOverlay) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "UPDATE `ret_chains` SET `name`=?, `allow_chainusers_access_to_otheruserdata`=?, `allow_admin_access_to_userdata`=?, seperate_not_and_analyzed=?, show_save_pdf_option=?, hide_pdf=?, hide_download_image_with_overlay=? WHERE `chain_id`=?";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("siiiiiii", $chainName, $chainUsersAccess, $adminAccess, $separateLists, $showSavePDFOption, $hidePDF, $hideDownloadImageWithOverlay, $chainID);
        $stmt->execute();
        return true;
    }

    public function updatePDFSettingsOnChain($chainID, $logo, $showShopName, $showShopPhonenumber, $showClientName, $showClientID, $showShopAddress) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "INSERT INTO `ret_pdf_settings_chains` (chainID, logo, showShopName, showShopPhonenumber, showClientName, showClientID, showShopAddress) VALUES(?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE pdfChainID=LAST_INSERT_ID(pdfChainID), logo=VALUES(logo), showShopName=VALUES(showShopName), showShopPhonenumber=VALUES(showShopPhonenumber), showClientName=VALUES(showClientName), showClientID=VALUES(showClientID), showShopAddress=VALUES(showShopAddress)";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("iiiiiii", $chainID, $logo, $showShopName, $showShopPhonenumber, $showClientName, $showClientID, $showShopAddress);
        $stmt->execute();
        return $this->mysqli->insert_id;
    }

    public function updateLogoFilePathPDFSettings($pdfID, $logoPath) {
        $query = "UPDATE `ret_pdf_settings_chains` SET `logo_file_path` = ? WHERE `ret_pdf_settings_chains`.`pdfChainID` = ?;";
        return $this->performQueryGetResult($query, 'si', [$logoPath, $pdfID]);
    }

    ////////////////////////////////////////
    // Functions related to pdf
    ////////////////////////////////////////

    public function getChainPDFSettings($chainID) {
        $query = "SELECT * FROM `ret_pdf_settings_chains` WHERE chainID = ?;";
        return $this->performQueryGetResult($query, 'i', [$chainID]);
    }
    
    ////////////////////////////////////////
    // Functions related to distributors
    ////////////////////////////////////////

    public function getDistributorFromCountryCode($countryCode) {
        $query = "SELECT * FROM `ret_distributor_country` WHERE countryCode = ?;";
        return $this->performQueryGetResult($query, 's', [$countryCode]);
    }

    public function getDistributorInfoFromDistributorID($distributorID) {
        $query = "SELECT * FROM `ret_distributor_info` WHERE distributorID = ?;";
        return $this->performQueryGetResult($query, 'i', [$distributorID]);
    }
    
    public function getDistributorCountries(){
        $query = "SELECT * FROM `ret_distributor_country` ORDER BY countryCode;";
        return $this->performQueryGetResult($query, '', []);
    }
    
    ////////////////////////////////////////
    // Functions related to analysis initiated
    ////////////////////////////////////////
    
    public function addAnalysisInitiated($reqID, $userID, $imgID, $type, $username) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "INSERT INTO `ret_analysis_initiated` (`reqID`, `userID`, `imgID`, `type`, `username`) VALUES (?, ?, ?, ?, ?);";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("siiis", $reqID, $userID, $imgID, $type, $username);
        $stmt->execute();
        return $this->mysqli->insert_id;
    }
    
    public function getAnalysisInitiated(){
        $query = "SELECT * FROM ret_analysis_initiated;";
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function cleanAnalysisInitiated(){
        $query = "DELETE FROM `ret_analysis_initiated`;";
        return $this->performQueryGetResult($query, '', []);
    }
    
    ////////////////////////////////////////
    // Functions related to Authentication
    ////////////////////////////////////////
    
    public function getUserInfoOnImage($imgID){        
        $query = "SELECT * FROM ret_image_info INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID LEFT JOIN ret_user_settings ON ret_image_info.userID = ret_user_settings.userID WHERE id = ?;";        
        return $this->performQueryGetResult($query, 'i', [$imgID]);
    }
    
    public function getCountryFromUserID($userID){
        $query = "SELECT country FROM ret_user_info WHERE userID = ?;";        
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    public function getChainIDFromUserID($userID){
        $query = "SELECT chainID FROM ret_users WHERE userID = ?;";        
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    public function getLBRegionFromUserID($userID){
        $query = "SELECT lbRegion FROM ret_users WHERE userID = ?;";        
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    public function getAnalysisAllowedSettingsUserID($userID){
        $query = "SELECT * FROM ret_user_settings WHERE userID = ?;";        
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    ////////////////////////////////////////
    // Functions related to Annotation
    ////////////////////////////////////////
    
    public function getNotAnnotatedImageID(){
        $offset_query = "SELECT FLOOR(RAND() * COUNT(*)) AS `offset` FROM `ret_image_info` LEFT JOIN ret_users ON ret_users.userID = ret_image_info.userID WHERE username IS NOT NULL AND refID IS NOT NULL";
        $offset_row = $this->performQueryGetResult($offset_query, '', [])->fetch_object();
        $offset = $offset_row->offset;
        $result_query = "SELECT id FROM `ret_image_info` LEFT JOIN ret_users ON ret_users.userID = ret_image_info.userID WHERE username IS NOT NULL AND refID IS NOT NULL LIMIT ?, 1";
        return $this->performQueryGetResult($result_query, 'i', [$offset])->fetch_object()->id;
    }
    
    public function getNotCheckedImageID($chainID){
        $imageID_query = "SELECT imageID FROM ret_annotate INNER JOIN ret_users ON ret_annotate.userID = ret_users.userID WHERE chainID = ? AND checkedByOphUserID IS NULL LIMIT 1;";
        $imageID_result = $this->performQueryGetResult($imageID_query, 'i', [$chainID])->fetch_object();
        $imageID = $imageID_result->imageID;
        $result_query = "SELECT id FROM `ret_image_info` WHERE id = ?";
        return $this->performQueryGetResult($result_query, 'i', [$imageID])->fetch_object()->id;
    }
    
    public function getAllQualityAnnotationAnswers(){
        $query = "SELECT * FROM ret_annotate_quality_answers;";        
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function getAllPathologiesAnnotationAnswers(){
        $query = "SELECT * FROM ret_annotate_pathologies_answers;";        
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function getAllLateralityAnnotationAnswers(){
        $query = "SELECT * FROM ret_annotate_laterality_answers;";        
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function getAllCenteringAnnotationAnswers(){
        $query = "SELECT * FROM ret_annotate_centering_answers;";        
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function getAllTextAnnotationAnswers(){
        $query = "SELECT * FROM ret_annotate_text_answers;";        
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function getAllImageTypeAnnotationAnswers(){
        $query = "SELECT * FROM ret_annotate_imagetype_answers;";        
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function createUpdateAnnotationFromFormData($imageID, $pathologiesAnnotationAnswers, $qualityAnnotationAnswer, $lateralityAnnotationAnswer, $centeringAnnotationAnswer, $textAnnotationAnswer, $imageTypeAnnotationAnswer, $userID, $optUserID) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->mysqli->autocommit(false);
            $query = 'INSERT INTO `ret_annotate` (annotateID, imageID, annotationDateTime, qualityAnnotateID, lateralityAnnotateID, textAnnotateID, centeringAnnotateID, imagetypeAnnotateID, userID, checkedByOphUserID) VALUES(NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE annotationDateTime=VALUES(annotationDateTime), qualityAnnotateID=VALUES(qualityAnnotateID), lateralityAnnotateID=VALUES(lateralityAnnotateID), textAnnotateID=VALUES(textAnnotateID), centeringAnnotateID=VALUES(centeringAnnotateID), imagetypeAnnotateID=VALUES(imagetypeAnnotateID), userID=VALUES(userID), checkedByOphUserID=VALUES(checkedByOphUserID);';
            $mysqldate = DatabaseTime::getCurrentTime();
        
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("isiiiiiii", $imageID, $mysqldate, $qualityAnnotationAnswer, $lateralityAnnotationAnswer, $textAnnotationAnswer, $centeringAnnotationAnswer, $imageTypeAnnotationAnswer, $userID, $optUserID);
            $stmt->execute();

            $annotateID = $this->mysqli->insert_id;
            
            $query = "DELETE FROM `ret_annotate_pathologies_relations` WHERE `annotateID` = ?;";        
            $this->performQueryGetResult($query, 'i', [$annotateID]);
            
            foreach ($pathologiesAnnotationAnswers as $pathologiesAnnotationAnswer) {
                $query3 = "INSERT INTO ret_annotate_pathologies_relations (annotateID, pathologiesAnnotateID) VALUES (?,?)";
                $stmt3 = $this->mysqli->prepare($query3);
                $stmt3->bind_param("ii", $annotateID, $pathologiesAnnotationAnswer);
                $stmt3->execute();
            }
        } catch (\Exception $ex) {
            $this->mysqli->rollback();
            error_log("Error: Could not update annotation. Paths: " . print_r($pathologiesAnnotationAnswers, true));
            throw $ex;
        } finally {
            $this->mysqli->autocommit(true);
        }
    }
    
    public function getNumberOfAnnotatedImages(){
        $query = "SELECT COUNT(*) FROM ret_annotate;";        
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function getAnnotationInfo($imageID) {
        $query = "SELECT * FROM ret_annotate WHERE imageID = ?;";        
        return $this->performQueryGetResult($query, 'i', [$imageID]);
    }
    
    public function getAnnotatePathologiesRelations($annotateID) {
        $query = "SELECT * FROM ret_annotate_pathologies_relations WHERE annotateID = ?;";        
        return $this->performQueryGetResult($query, 'i', [$annotateID]);
    }
    
    public function getAlreadyAnnotated($userID) {
        $query = "SELECT * FROM `ret_annotate` INNER JOIN ret_image_info ON ret_image_info.id = ret_annotate.imageID INNER JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID  WHERE ret_annotate.userID = ?;";     
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    public function getAlreadyAnnotatedOpt($userID) {
        $query = "SELECT * FROM `ret_annotate` INNER JOIN ret_image_info ON ret_image_info.id = ret_annotate.imageID INNER JOIN ret_customers ON ret_image_info.CustomerID = ret_customers.CustomerID INNER JOIN ret_users ON ret_image_info.userID = ret_users.userID LEFT JOIN ret_referral ON ret_image_info.refID = ret_referral.refID LEFT JOIN ret_glaucoma_info ON ret_image_info.glaucomaID = ret_glaucoma_info.glaucomaID  WHERE ret_annotate.checkedByOphUserID = ?;";     
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    ////////////////////////////////////////
    // Functions related to Harmony API
    ////////////////////////////////////////
    
    public function addRequestHarmony($userID, $requestData) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "INSERT INTO `ret_api_harmony_requests` (`userID`, `requestData`) VALUES (?, ?);";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("is", $userID, $requestData);
        $stmt->execute();
        return $this->mysqli->insert_id;
    }
    
    public function getRequestHarmony($harmonyReqID){
        $query = "SELECT * FROM ret_api_harmony_requests WHERE harmonyReqID = ?;";
        return $this->performQueryGetResult($query, 'i', [$harmonyReqID]);
    }
    
    public function updateRequestHarmonyResultReady($harmonyReqID){
        $query = "UPDATE `ret_api_harmony_requests` SET `resultReady` = '1' WHERE (`harmonyReqID` = ?);";
        return $this->performQueryGetResult($query, 'i', [$harmonyReqID]);
    }
    
    public function checkIfFilenameExists($filename, $userID){
        $query = "SELECT id FROM retinalyze.ret_image_info WHERE filename LIKE ? AND userID = ? LIMIT 1;";
        return $this->performQueryGetResult($query, 'si', [$filename, $userID]);
    }
    
    ////////////////////////////////////////
    // Internal functions
    ////////////////////////////////////////
    
    private function performQueryGetResult($query, $bindTypes, $parameters){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $stmt = $this->mysqli->prepare($query);
        if(!empty($parameters)){
            $stmt->bind_param($bindTypes, ...$parameters);
        }
        $stmt->execute();
        $return = $stmt->get_result();
        $stmt->close();
        return $return;
    }
    
    ////////////////////////////////////////
    // Convert s3 functions
    ////////////////////////////////////////
    
    public function getConversionStatus($userID) {
        $query = "SELECT * FROM ret_users WHERE userID = ?;";        
        return $this->performQueryGetResult($query, 'i', [$userID]);
    }
    
    public function getUsersNotConverted() {
        $query = "SELECT userID FROM ret_users WHERE s3usingUserID = 0 ORDER BY userID ASC;";        
        return $this->performQueryGetResult($query, '', []);
    }
    
    public function updateConversionStatus($userID, $status){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $query = "UPDATE ret_users SET s3usingUserID = ? WHERE userID = ?;";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("ii", $status, $userID);
        $stmt->execute();
    }
}
