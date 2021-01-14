<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Authentication;

use RetinaLyze\Database\DatabaseHandler;
use RetinaLyze\Login\LoginChecker;
use RetinaLyze\Utils\AdminUtils;

/**
 * Description of CheckAuthentication
 *
 * @author mom
 */
class CheckAuthentication {
    private $dbh;
    private $loginChecker;
    
    
    function __construct($dbh = NULL) {
        if($dbh == NULL){
            $this->dbh = new DatabaseHandler();
        }else{
            $this->dbh = $dbh;
        }
        $this->loginChecker = new LoginChecker(false);
    }
    
    /**
     * Check if current user have access to image data. If not a 401 will be sent to the browser. If the user have access it will return true
     * @param int $imgID
     * @return boolean
     */
    
    public function checkUserAccessToImage($imgID, $paid = NULL){
        
        $currentUserID = $this->getUserID();
        //Check if user is super admin
        $access = $this->checkIfAllAccessUser();
        if($access === true){
            return true;
        }
        
        if(!empty($paid)){
            //Get image info
            $imageInfoDB = $this->dbh->getSingleAnalyzedImgInfoWithUsername($imgID);
            $imageInfo = $imageInfoDB->fetch_assoc();
            $databasePAID = $imageInfo["pid"];
            if ($paid == $databasePAID) {
                return true;
            }
        }
        
        //Get the userID and user info of the image
        try{
            $imageOwnerUserDataDB = $this->dbh->getUserInfoOnImage($imgID);
            $imageOwnerUserData = $imageOwnerUserDataDB->fetch_assoc();
            $imageOwnerUserID = $imageOwnerUserData['userID'];
        } catch (\Exception $ex) {
            \error_log("Error: Could not get userID from imgID, " . $ex->getFile() . ",Line: " .  $ex->getLine() . "\n Message: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
            $this->sentUnauthorized();
            die();
        }
        
        //Check if global eye specialist have access to the image
        if($this->checkUserTypeID(3) === true && $imageOwnerUserData['referral'] === 1){
            return true;
        }
        
        //Check if local eye specialist have access to the image
        if($this->checkUserTypeID(7) === true){
            try{
                $currentUserDataDB = $this->dbh->getLBRegionFromUserID($currentUserID);
                $currentUserData = $currentUserDataDB->fetch_assoc();
            } catch (\Exception $ex) {
                \error_log("Error: Could not get lb region from imgID, " . $ex->getFile() . ",Line: " .  $ex->getLine() . "\n Message: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
                $this->sentUnauthorized();
                die();
            }
            if($imageOwnerUserData['lbRegion'] === $currentUserData['lbRegion']){
                return true;
            }
        }
        
        //Check if the user accessing and user owner is the same
        if($imageOwnerUserID == $currentUserID){
            return true;
        }
        //Check if chain users/Admins has access
        $currentUsersChainID = $this->getUsersChainID();
        $imageOwnerChainID = $this->getUsersChainID($imageOwnerUserID);
        if($currentUsersChainID !== false && $imageOwnerChainID !== false && $currentUsersChainID == $imageOwnerChainID && $this->checkUserTypeID(2) && $this->checkIfChainAllowsChainUsersAccessToData($imageOwnerChainID)){
            return true;
        }
        if($currentUsersChainID !== false && $imageOwnerChainID !== false && $currentUsersChainID == $imageOwnerChainID && $this->checkUserTypeID(6) && $this->checkIfChainAllowsChainAdminsAccessToData($imageOwnerChainID)){
            return true;
        }   
        
        $this->sentUnauthorized();
        die();
    }
    
    /**
     * Check if current user have access to analyze photo and access to the type of analysis
     * @param int $chainID
     * @return boolean
     */
    
    public function checkUserAccessToAnalyzeImage($imgID, $analysisType){
        $this->checkUserAccessToImage($imgID);
        if($this->checkUserAnalysisTypeAccess($analysisType)){
            return true;
        }else{
            $this->sentUnauthorized();
            die();
        }
    }
    
    /**
     * Check if current user have access to chain data. If not a 401 will be sent to the browser. If the user have access it will return true
     * @param int $chainID
     * @return boolean
     */
    
    public function checkUserAccessToChain($chainID){
        if($this->checkIfAllAccessUser() === true){
            return true;
        }
        if($this->checkFranchaiseAccessToChain($chainID) === true){
            return true;
        }
        if($this->checkChainAdministratorAccessToChain($chainID) == true){
            return true;
        }
        
        $this->sentUnauthorized();
        die();
    }
    
    /**
     * Check if current user have access to all data. If not a 401 will be sent to the browser. If the user have access it will return true
     * @param int $chainID
     * @return boolean
     */
    
    public function checkIfAccessToAllData(){
        if($this->checkUserTypeID(1) === true){
            return true;
        }else{
            $this->sentUnauthorized();
            die();
        }
    }
    
    /**
     * Check if current user have access to country data. If not a 401 will be sent to the browser. If the user have access it will return true
     * @param string $country
     * @return boolean
     */
    
    public function checkUserAccessToCountry($country){
        if($this->checkIfAllAccessUser() === true){
            return true;
        }
        if($this->checkFranchaiseAccessToCountry($country) === true){
            return true;
        }
        $this->sentUnauthorized();
        die();
    }
    
    /**
     * Check if current user have access to user data. If not a 401 will be sent to the browser. If the user have access it will return true
     * @param int $equalUserID
     * @return boolean
     */
    
    public function checkUserAccessToUser($equalUserID){
        if($this->checkIfAllAccessUser() === true){
            return true;
        }
        $userID = $this->getUserID();
        if($equalUserID == $userID){
            return true;
        }
        $equalUsersCountry = $this->getUsersCountry($equalUserID);
        if($this->checkFranchaiseAccessToCountry($equalUsersCountry) === true){
            return true;
        }
        $equalUsersChainID = $this->getUsersChainID($equalUserID);
        if($this->checkChainAdministratorAccessToChain($equalUsersChainID) == true){
            return true;
        }
        $this->sentUnauthorized();
        die();
    }
    
    /**
     * Get a SQL formatted list of users the current user have access to. 
     * @return string
     */
    
    public function getSQLListOfUsersCurrentUserHaveAccessTo(){
        if($this->checkIfAllAccessUser() === true){
            return 'ALL';
        }
        if($this->checkUserTypeID(5)){ //Franchaise user
            return false;
        }
        $userChainID = $this->getUsersChainID();
        if($userChainID !== false && $this->checkUserTypeID(2) && $this->checkIfChainAllowsChainUsersAccessToData($userChainID)){
            return $this->getListOfUsersInChain($userChainID);
        }
        if($userChainID !== false && $this->checkUserTypeID(6) && $this->checkIfChainAllowsChainAdminsAccessToData($userChainID)){
            return $this->getListOfUsersInChain($userChainID);
        }
        return $this->getUserID();
    }
    
    /**
     * Returns true if the chain allows chain users (not admins) to access the other users inside the chain data. 
     * @return boolean
     */
    
    public function checkIfChainAllowsChainUsersAccessToData($chainID) {
        try {
            if($chainID != null){
                $chainDataDB = $this->dbh->getChainFromChainID($chainID);
            }else{
                return false;
            }            
        } catch (\Exception $ex) {
            error_log("Error: Could not get chainID from userID, " . $ex->getFile() . ",Line: " .  $ex->getLine() . "\n Message: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
            return false;
        }
        if(!empty($chainDataDB->num_rows) && $chainDataDB->num_rows == 1){
            $chainData = $chainDataDB->fetch_assoc();
            if($chainData["allow_chainusers_access_to_otheruserdata"] == 1){
                return true;
            }
        }
        return false;
    }
    
    /**
     * Returns true if the chain allows chain admin users to access the other users inside the chain data. 
     * @return boolean
     */
    
    public function checkIfChainAllowsChainAdminsAccessToData($chainID) {
        try {
            $chainDataDB = $this->dbh->getChainFromChainID($chainID);
        } catch (\Exception $ex) {
            error_log("Error: Could not get chainID from userID, " . $ex->getFile() . ",Line: " .  $ex->getLine() . "\n Message: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
            return false;
        }
        if(!empty($chainDataDB->num_rows) && $chainDataDB->num_rows == 1){
            $chainData = $chainDataDB->fetch_assoc();
            if($chainData["allow_admin_access_to_userdata"] == 1){
                return true;
            }
        }
        return false;
    }
    
    /**
     * Returns true if users roleID matches parameter ID
     * @return boolean
     */
    
    public function checkUserRoleID($equalsUserUserRoleID) {
        $userRoleID = $this->loginChecker->getUserRoleID();
        if($userRoleID !== false){
            if($userRoleID == $equalsUserUserRoleID){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
    public function checkIfAllAccessUser(){
        return $this->checkUserTypeID(1) || $this->checkUserTypeID(8) || $this->checkUserTypeID(9);
    }
    
    private function checkFranchaiseAccessToChain($chainID) {
        $userType = $this->loginChecker->getTypeID();
        if($userType != 5){
            return false;
        }     
        $country = $this->getUsersCountry();
        $au = new AdminUtils();
        $allowedChains = $au->getChainsFromCountry($country);
        foreach ($allowedChains as $allowedChain) {
            if ($allowedChain["id"] == $chainID) {
                return true;
            }
        }
        return false;
    }
    
    private function checkFranchaiseAccessToCountry($country) {
        $userType = $this->loginChecker->getTypeID();
        if($userType != 5){
            return false;
        }     
        $usersCountry = $this->getUsersCountry();
        if($usersCountry == $country){
            return true;
        }else{
            return false;
        }
    }
    
    private function checkChainAdministratorAccessToChain($chainID) {
        if($this->checkUserTypeID(6) !== true){
            return false;
        }
        $usersChainID = $this->getUsersChainID();
        if($usersChainID == $chainID){
            return true;
        }else{
            return false;
        }
    }    
    
    private function getUserID(){
        $userID = $this->loginChecker->getUserID();
        if($userID === false){
            return false;
        }
        return $userID;
    }
    
    private function sentUnauthorized(){
        ob_clean();
        header('HTTP/1.0 401 Unauthorized');
        echo 'Unauthorized.';
        die();
    }
    
    
    private function getUsersCountry($userID = false) {
        if($userID === false){
            $userID = $this->getUserID();
        }
        try {
            $userDateDB = $this->dbh->getCountryFromUserID($userID);
        } catch (\Exception $ex) {
            error_log("Error: Could not get country from userID, " . $ex->getFile() . ",Line: " .  $ex->getLine() . "\n Message: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
            $this->sentUnauthorized();
            die();
        }
        if(!empty($userDateDB->num_rows) && $userDateDB->num_rows == 1){
            $userData = $userDateDB->fetch_assoc();
            return $userData["country"];
        }else{
            $this->sentUnauthorized();
            die();
        }        
    }
    
    private function getUsersChainID($userID = false) {
        if($userID === false){
            $userID = $this->getUserID();
        }
        try {
            $userDateDB = $this->dbh->getChainIDFromUserID($userID);
        } catch (\Exception $ex) {
            error_log("Error: Could not get chainID from userID, " . $ex->getFile() . ",Line: " .  $ex->getLine() . "\n Message: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
            return false;
        }
        if(!empty($userDateDB->num_rows) && $userDateDB->num_rows == 1){
            $userData = $userDateDB->fetch_assoc();
            return $userData["chainID"];
        }else{
            return false;
        }        
    }
    
    private function checkResultIsEqualMySQL($result, $column, $equals) {
        if(!empty($result->num_rows) && $result->num_rows == 1){
            $userIDResult = $result->fetch_assoc();   
            if($userIDResult[$column] == $equals){
                return true;
            }else{
                $this->sentUnauthorized();
                die();
            }
        }else{
            $this->sentUnauthorized();
            die();
        }
    }
    
    private function checkUserTypeID($equalsUserTypeID) {
        $userType = $this->loginChecker->getTypeID();
        if($userType !== false){
            if($userType == $equalsUserTypeID){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
    private function checkUserAnalysisTypeAccess($analysisType) {
        $userID = $this->getUserID();
        $userAnalysisTypeSettingsDB = $this->dbh->getAnalysisAllowedSettingsUserID($userID);
        if(!empty($userAnalysisTypeSettingsDB->num_rows) && $userAnalysisTypeSettingsDB->num_rows == 1){
            $userAnalysisTypeSettings = $userAnalysisTypeSettingsDB->fetch_assoc();
            if($userAnalysisTypeSettings['onlyESB']){
                return false;
            }else if($userAnalysisTypeSettings['AMDenabled'] === 1 && $analysisType === '1'){
                return true;    
            }else if($userAnalysisTypeSettings['DRenabled'] === 1 && $analysisType === '0'){
                return true;    
            }else if($userAnalysisTypeSettings['GlaucomaEnabled'] === 1 && $analysisType === '2'){
                return true;    
            }
            return false;
        }else{
            return false;
        }  
    }
    
    private function getListOfUsersInChain($chainID) {
        try {
            $usersDataDB = $this->dbh->getUsersFromChain($chainID);
        } catch (\Exception $ex) {
            error_log("Error: Could not get chainID from userID, " . $ex->getFile() . ",Line: " .  $ex->getLine() . "\n Message: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
            return false;
        }
        if(!empty($usersDataDB->num_rows)){
            $returnString = '';
            while($usersData = $usersDataDB->fetch_assoc()){
                $returnString .= $usersData['userID'] . ',';
            }
            return rtrim($returnString,", ");
        }
        return '';
    }
}
