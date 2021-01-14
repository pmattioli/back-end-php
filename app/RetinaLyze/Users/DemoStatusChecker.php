<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Users;

use RetinaLyze\Database\DatabaseHandler;
/**
 * The DemoStatusChecker is used to check the demo status for a given user, and to add used analysis.
 * The demo status is split into three different groups one for DR and AMD analysis, one for ophthalmologist backup and one for Glaucoma analysis
 *
 * @author mom
 */
class DemoStatusChecker {
    
    private $userID;
    private $demoDetails;
    private $dbHandler;
    
    function __construct($userID, $dbh = NULL) {
        $this->userID = $userID;
        if($dbh == NULL){
            $this->dbHandler = new DatabaseHandler();
        }else{
            $this->dbHandler = $dbh;
        }
        $demoStatus = $this->dbHandler->getDemoStatus($this->userID);
        $this->demoDetails = $demoStatus->fetch_assoc();
    }

    
    /**
     * Get the DR and AMD status for a given user with a userID. 
     * If the limit is reached the function returns false. 
     * If the limit is not reached or there is no limit the function returns true
     * 
     * @return boolean If there is a limit and it is reached it returns false
     */
    public function getDRAndAMDStatus() {
        if (!empty($this->demoDetails)){
            $this->checkActivationStatus($this->demoDetails["demoStatusDRandAMD"], $this->demoDetails["prepaidStatusDRandAMD"], 1);
            return $this->getStatus($this->demoDetails["demoStatusDRandAMD"], $this->demoDetails["prepaidStatusDRandAMD"], $this->demoDetails["limitDRandAMD"], $this->demoDetails["usedDRandAMD"], $this->demoDetails['dateTimeExpiresDRandAMD']);
        }else{            
            error_log('Error: Check dr and amd demo setting for user with user ID: ' . $this->userID);
            return true;
        }
    }
    
    /**
     * Get the Glaucoma status for a given user with a userID. 
     * If the limit is reached the function returns false. 
     * If the limit is not reached or there is no limit the function returns true
     * 
     * @return boolean If there is a limit and it is reached it returns false
     */
    public function getGlaucomaStatus() {
        if (!empty($this->demoDetails)){
            $this->checkActivationStatus($this->demoDetails["demoStatusGlaucoma"], $this->demoDetails["prepaidStatusGlaucoma"], 2);
            return $this->getStatus($this->demoDetails["demoStatusGlaucoma"], $this->demoDetails["prepaidStatusGlaucoma"], $this->demoDetails["limitGlaucoma"], $this->demoDetails["usedGlaucoma"], $this->demoDetails['dateTimeExpiresGlaucoma']);
        }else{            
            error_log('Error: Check glaucoma demo setting for user with user ID: ' . $this->userID);
            return true;
        }
    }
    
    /**
     * Get the Ophthalmologist status for a given user with a userID. 
     * If the limit is reached the function returns false. 
     * If the limit is not reached or there is no limit the function returns true
     * 
     * @return boolean If there is a limit and it is reached it returns false
     */
    public function getOphthalmologistStatus() {
        if (!empty($this->demoDetails)){
            return $this->getStatus($this->demoDetails["demoStatusOphthal"], $this->demoDetails["prepaidStatusOphthal"], $this->demoDetails["limitOphthal"], $this->demoDetails["usedOphthal"], $this->demoDetails['dateTimeExpiresOphthal']);
        }else{            
            error_log('Error: Check opthal demo setting for user with user ID: ' . $this->userID);
            return true;
        }
    }
    
    private function getStatus($demoStatus, $prepaidStatus, $limit, $used, $dateExpires){
        if ($demoStatus == 2 || $prepaidStatus == 2) {
            error_log('Error: something went wrong. Status is 2 and it should not be possible. Check userID: ' . $this->userID);
            error_log('Demostatus: ' . $demoStatus);
            error_log('Prepaidstatus: ' . $prepaidStatus);
            error_log('Limit: ' . $limit);
            error_log('Used: ' . $used);
            return false;
        }
        if ($demoStatus == 1 || $prepaidStatus == 1) {
            if($limit != NULL){
                $used = empty($used) ? 0 : $used;
                if ($limit <= $used) {
                    return false; //Doesn't have any more demos
                }
            }
            if(!empty($dateExpires)){
                $dateTimeExpires = \DateTime::createFromFormat("Y-m-d H:i:s", $dateExpires);
                $dateTimeNow = new \DateTime("now");
                if ($dateTimeExpires < $dateTimeNow) {
                    return false; //Doesn't have any more demos
                }
            }
        }
        return true;
    }
    
    /**
     * This function check if the demo setting is set to 2 (not activated). If it is 2 then it set it to 1 and add the number of days to dateTimeExpires
     * 
     * @param int $demoStatus The current demo status of the analysis type, which should be checked
     * @param int $prepaidStatus The current prepaid status of the analysis type, which should be checked
     * @param int $analysisType The analysis type, which should be checked (1 = DR/AMD, 2 = Glaucoma)
     */
    
    private function checkActivationStatus($demoStatus, $prepaidStatus, $analysisType) {
        if ($demoStatus == 2) {
            $dpa = new DemoPrepaidAdjuster($this->userID);
            if($analysisType == 1){
                $dpa->setDRandAMDDemoPrepaidStatusDays(1, $this->demoDetails["daysWhenActivatedDRandAMD"], $this->demoDetails["limitDRandAMD"], 0);
            }elseif ($analysisType == 2) {
                $dpa->setGlaucomaDemoPrepaidStatusDays(1, $this->demoDetails["daysWhenActivatedGlaucoma"], $this->demoDetails["limitGlaucoma"], 0);
            }
            //Update demo status since it has changed.
            $demoStatus = $this->dbHandler->getDemoStatus($this->userID);
            $this->demoDetails = $demoStatus->fetch_assoc();
        }
        if ($prepaidStatus == 2) {
            $dpa = new DemoPrepaidAdjuster($this->userID);
            if($analysisType == 1){
                $dpa->setDRandAMDDemoPrepaidStatusDays(0, $this->demoDetails["daysWhenActivatedDRandAMD"], $this->demoDetails["limitDRandAMD"], 1);
            }elseif ($analysisType == 2) {
                $dpa->setGlaucomaDemoPrepaidStatusDays(0, $this->demoDetails["daysWhenActivatedGlaucoma"], $this->demoDetails["limitGlaucoma"], 1);
            }
            //Update demo status since it has changed.
            $demoStatus = $this->dbHandler->getDemoStatus($this->userID);
            $this->demoDetails = $demoStatus->fetch_assoc();
        }
    }
    
    /**
     * Add a used analysis to the count of used DR and AMD analysis
     */
    public function useDRandAMDAnalysis($analysisType, $imgID){
        $this->dbHandler->increaseDRandAMDUsed($this->userID);
        if($analysisType == 1){
            $this->useAnalysis($this->demoDetails["demoStatusDRandAMD"], $this->demoDetails["prepaidStatusDRandAMD"], $imgID, 2);
        } elseif ($analysisType == 0) {
            $this->useAnalysis($this->demoDetails["demoStatusDRandAMD"], $this->demoDetails["prepaidStatusDRandAMD"], $imgID, 1);
        }
    }
    
    /**
     * Add a used analysis to the count of used Glaucoma analysis
     */
    public function useGlaucomaAnalysis($imgID){
        $this->dbHandler->increaseGlaucomaUsed($this->userID);
        $this->useAnalysis($this->demoDetails["demoStatusGlaucoma"], $this->demoDetails["prepaidStatusGlaucoma"], $imgID, 3);
    }
    
    /**
     * Add a used ref to the count of used Ophthalmologist ref
     */
    public function useOphthalAnalysis(){
        $this->dbHandler->increaseOphthalUsed($this->userID);
    }
    
    private function useAnalysis($demoStatus, $prepaidStatus, $imgID, $analysisType) {
        if ($prepaidStatus == 1) {
            $this->dbHandler->inputDemoPrepaidDetailsDRandAMD($imgID, $analysisType, 2);
        }else if($demoStatus == 1){
            $this->dbHandler->inputDemoPrepaidDetailsDRandAMD($imgID, $analysisType, 1);
        }
    }
}
