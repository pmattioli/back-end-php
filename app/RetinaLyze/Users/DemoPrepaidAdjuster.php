<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Users;

use RetinaLyze\Database\DatabaseHandler;
/**
 * Use this class to adjust the demo status of a user
 *
 * @author mom
 */
class DemoPrepaidAdjuster {
    private $userID;
    private $dbh;
    
    function __construct($userID) {
        $this->dbh = new DatabaseHandler();
        $this->userID = $userID;
    }
    
    /**
     * setGlaucomaDemoStatusStandard will set the glaucoma demo status for the user with standard settings.
     * 
     */
    
    public function setGlaucomaDemoStatusStandard($status) {
        if($status == true){
            $demoLimit = 50;
            $days = 31;
            $demoStatus = 2;
        }else{
            $demoLimit = NULL;
            $days = NULL;
            $demoStatus = 0;
        }
        $this->setGlaucomaDemoPrepaidStatus($demoStatus, null, $demoLimit, 0, $days);
    }
    
    /**
     * setGlaucomaDemoStatusMonths will set the glaucoma demo status for the user for a number of months.
     * 
     * @param boolean $status 0 = Not demo user, 1 = Activated demo user, 2 = Unactivated demo user
     * @param int $days The number of days when the trial should end. If null then there is no limit set.
     * @param int $limit The number of allowed screenings. If null then there is no limit set.
     * @paran int $prepaid 0 = Not prepaid user, 1 = Activated prepaid user, 2 = Unactivated prepaid user
     */
    
    public function setGlaucomaDemoPrepaidStatusDays($status, $days, $limit, $prepaid) {
        if($days === NULL){
            $this->setGlaucomaDemoPrepaidStatus($status, null, $limit, $prepaid, $days);
        }else{
            $demoLimitTime = new \DateTime();
            $demoLimitTime->add(new \DateInterval('P' . $days .'D'));
            $demoLimitTimeDB = $demoLimitTime->format('Y-m-d H:i:s');
            $this->setGlaucomaDemoPrepaidStatus($status, $demoLimitTimeDB, $limit, $prepaid, $days);
        }
    }
    
    /**
     * setDRandAMDDemoStatusStandard will set the DR and AMD demo status for the user with standard settings.
     * 
     */
    
    public function setDRandAMDDemoStatusStandard($status) {
        if($status == true){
            $demoLimit = 200;
            $days = 31;
            $demoStatus = 2;
        }else{
            $demoLimit = NULL;
            $days = NULL;
            $demoStatus = 0;
        }
        $this->setDRandAMDDemoPrepaidStatus($demoStatus, null, $demoLimit, 0, $days);
    }
    
    /**
     * setDRandAMDDemoStatusMonths will set the DR and AMD demo status for the user for a number of months.
     * 
     * @param int $status 0 = Not demo user, 1 = Activated demo user, 2 = Unactivated demo user
     * @param int $months The number of months when the trial should end. If null then there is no limit set.
     * @param int $limit The number of allowed screenings. If null then there is no limit set.
     * @param int $prepaid 0 = Not prepaid user, 1 = Activated prepaid user, 2 = Unactivated prepaid user
     */
    
    public function setDRandAMDDemoPrepaidStatusDays($status, $days, $limit, $prepaid) {
        if($days === NULL){
            $this->setDRandAMDDemoPrepaidStatus($status, null, $limit, $prepaid, $days);
        }else{
            $demoLimitTime = new \DateTime();
            $demoLimitTime->add(new \DateInterval('P' . $days .'D'));
            $demoLimitTimeDB = $demoLimitTime->format('Y-m-d H:i:s');
            $this->setDRandAMDDemoPrepaidStatus($status, $demoLimitTimeDB, $limit, $prepaid, $days);
        }
    }
    
    /**
     * setDRandAMDDemoStatus will set the DR and AMD demo and prepaid status for the user.
     * 
     * @param int $status 0 = Not demo user, 1 = Activated demo user, 2 = Unactivated demo user
     * @param string $date The date when the trial should end. If null then there is no limit set.
     * @param int $limit The number of allowed screenings. If null then there is no limit set.
     * @param int $prepaid 0 = Not prepaid user, 1 = Activated prepaid user, 2 = Unactivated prepaid user
     * @param int $daysWhenActivatedDRandAMD The number of days which should be the period of the demo/prepaid period when activated
     */
    
    public function setDRandAMDDemoPrepaidStatus($status, $date, $limit, $prepaid, $daysWhenActivatedDRandAMD = null) {
        $this->dbh->updateDRandAMDDemoStatusAndLimit($this->userID, $status, $limit, $date, $prepaid, $daysWhenActivatedDRandAMD);
    }
    
    /**
     * setGlaucomaDemoPrepaidStatus will set the DR and AMD demo and prepaid status for the user.
     * 
     * @param int $status 0 = Not demo user, 1 = Activated demo user, 2 = Unactivated demo user
     * @param string $date The date when the trial should end. If null then there is no limit set.
     * @param int $limit The number of allowed screenings. If null then there is no limit set.
     * @param int $prepaid 0 = Not prepaid user, 1 = Activated prepaid user, 2 = Unactivated prepaid user
     * @param int $daysWhenActivatedGlaucoma The number of days which should be the period of the demo/prepaid period when activated
     */
    
    public function setGlaucomaDemoPrepaidStatus($status, $date, $limit, $prepaid, $daysWhenActivatedGlaucoma = null) {
        $this->dbh->updateGlaucomaDemoStatusAndLimit($this->userID, $status, $limit, $date, $prepaid, $daysWhenActivatedGlaucoma);
    }
    
    /**
     * setOphthalDemoPrepaidStatus will set the DR and AMD demo and prepaid status for the user.
     * 
     * @param int $status 0 = Not demo user, 1 = Activated demo user, 2 = Unactivated demo user
     * @param string $date The date when the trial should end. If null then there is no limit set.
     * @param int $limit The number of allowed screenings. If null then there is no limit set.
     * @param int $prepaid 0 = Not prepaid user, 1 = Activated prepaid user, 2 = Unactivated prepaid user
     */
    
    public function updateOphthalDemoStatusAndLimit($status, $date, $limit, $prepaid) {
        $this->dbh->updateOphthalDemoStatusAndLimit($this->userID, $status, $limit, $date, $prepaid);
    }
}
