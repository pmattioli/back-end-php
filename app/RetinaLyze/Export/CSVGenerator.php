<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Export;


use RetinaLyze\Utils\AdminUtils;
use RetinaLyze\Statistics\StatisticsHandler;
use RetinaLyze\Analysis\ResultInterpreter;
use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of CSVGenerator
 *
 * @author mom
 */
class CSVGenerator {
    private $adminUtils;
    private $dbh;
    
    private $showGlaucoma;
    private $showDR;
    private $showAMD;
    private $showRef;
    
    function __construct() {
        $this->adminUtils = new AdminUtils();
        $this->dbh = new DatabaseHandler();
        $this->showDR = false;
        $this->showAMD = false;
        $this->showGlaucoma = false;
        $this->showRef = false;
    }

    public function generateCSVForCountries() {
        $activeCountries = $this->adminUtils->getCurrentlyUsedCountries();
        $statisticsHandler = new StatisticsHandler();
        $csvArray = array();
        foreach ($activeCountries as $country) {
            $dbData = $this->dbh->getStatisticsDataAllForCountryThisYear($country);
            $statictiscArray = $statisticsHandler->generateStatisticsFromDBData($dbData);
            foreach($statictiscArray as $date => $value){                        
                array_unshift($value, $country, $date);
                $csvArray[] = $value;
            }    
        }       
        $this->generateCSV($csvArray, ["Country", "Month", "DR count", "Red DR", "Yellow DR", "AMD", "Yellow AMD", "Glaucoma count", "Red Glaucoma", "Yellow Glaucoma", "Ungradable", "Assessments"]);
    }
    
    public function generateCSVForCountry($country, $outsideChain = true) {
        if($outsideChain){
            $dbData = $this->dbh->getUsersFromCountryOutsideChain($country);
        }else{
            $dbData = $this->dbh->getUsersFromCountry($country);
        }
        $statisticsHandler = new StatisticsHandler();
        $csvArray = array();
        while ($user = $dbData->fetch_assoc()) {
            $statisticDataDB = $this->dbh->getStatisticsDataAllForUserThisYear($user["userID"]);
            $statictiscArray = $statisticsHandler->generateStatisticsFromDBData($statisticDataDB);            
            foreach($statictiscArray as $date => $value){                        
                array_unshift($value, $user['username'], $date);
                $csvArray[] = $value;
            }
        }
        $this->generateCSV($csvArray, ["Username", "Month", "DR count", "Red DR", "Yellow DR", "AMD", "Yellow AMD", "Glaucoma count", "Red Glaucoma", "Yellow Glaucoma", "Ungradable", "Assessments"]);
    }
    
    public function generateCSVForChain($chain) {
        $dbData = $this->dbh->getUsersFromChain($chain);
        $statisticsHandler = new StatisticsHandler();
        $csvArray = array();
        while ($user = $dbData->fetch_assoc()) {
            $statisticDataDB = $this->dbh->getStatisticsDataAllForUserThisYear($user["userID"]);
            $statictiscArray = $statisticsHandler->generateStatisticsFromDBData($statisticDataDB);            
            foreach($statictiscArray as $date => $value){                        
                array_unshift($value, $user['username'], $date);
                $csvArray[] = $value;
            }
        }
        $this->generateCSV($csvArray, ["Username", "Month", "DR count", "Red DR", "Yellow DR", "AMD", "Yellow AMD", "Glaucoma count", "Red Glaucoma", "Yellow Glaucoma", "Ungradable", "Assessments"]);
    }
    
    public function generateCSVForUser($userID, $perMonth = true) {
        $statisticsHandler = new StatisticsHandler($perMonth);
        $statisticDataDB = $this->dbh->getStatisticsDataAllForUser($userID);
        $statictiscArray = $statisticsHandler->generateStatisticsFromDBData($statisticDataDB);   
        $this->checkArrayForZeroNumbers($statictiscArray);
        $csvArray = $this->createDataCSVArray($statictiscArray);
        
        if($perMonth){
            $csvHeaderArray = $this->createHeaderCSVArray(["Month"]);
            $this->generateCSV($csvArray, $csvHeaderArray, $userID);
        }else{
            $csvHeaderArray = $this->createHeaderCSVArray(["Date"]);
            $this->generateCSV($csvArray, $csvHeaderArray, $userID);
        }        
    }
    
    public function generateCSVForGlaucoma() {
        $statisticDataDB = $this->dbh->getStatisticsDataAllForGlaucoma();
        $csvArray = array();
        while ($statisticData = $statisticDataDB->fetch_assoc()) {            
            $imgID = $statisticData['imgID'] == NULL ? '' : $statisticData['imgID'];
            $userID = $statisticData['userID'] == NULL ? '' : $statisticData['userID'];
            $apiID = $statisticData['apiID'] == NULL ? '' : $statisticData['apiID'];
            $timeGlaucoma = $statisticData['timeGlaucomaStarted'] == NULL ? '' : $statisticData['timeGlaucomaStarted'];
            $glacomaResult = $statisticData['glaucomaResult'] == NULL ? '' : ResultInterpreter::getGlaucomaResultWithoutESBResult($statisticData['glaucomaResult']);
            
            $demo = $statisticData['demoType'] != 1 ? '' : 'true';
            $role = $statisticData['roleDescription'] == NULL ? '' : $statisticData['roleDescription'];
            
            $csvArray[] = [$apiID, $imgID, $userID, $timeGlaucoma, $glacomaResult, $demo, $role];
        }
        $this->generateCSV($csvArray, ["APIID", "ImgID", "UserID", "Time Of Glaucoma Analysis", "Glaucoma Result", "Demo", "Role"]);
    }
    
    private function generateCSV($array, $headerArray, $dataID = '') {
        if(empty($array)){
            die();
        }
        header("Content-Type: text/csv");
        header('Content-Disposition: attachment; filename=data' . $dataID . '.csv');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, $headerArray);
        foreach($array as $row){            
            fputcsv($fp, $row);
        }
        fclose($fp);
    }
    
    private function checkArrayForZeroNumbers($statistics){
        foreach($statistics as $row){
            if(!empty($row["dr"]) && $this->showDR == false){
                $this->showDR = true;
            }
            if(!empty($row["amd"]) && $this->showAMD == false){
                $this->showAMD = true;
            }
            if(!empty($row["glaucoma"]) && $this->showGlaucoma == false){
                $this->showGlaucoma = true;
            }
            if(!empty($row["ref"]) && $this->showRef == false){
                $this->showRef = true;
            }
            
        }
        
    }
    
    private function createDataCSVArray($statistics) {
        $csvArray = array();
        foreach($statistics as $date => $value){
            array_unshift($value, $date);
            if(!$this->showDR){
                unset($value["dr"]);
                unset($value["drRed"]);
                unset($value["drYellow"]);
            }
            if(!$this->showAMD){
                unset($value["amd"]);
                unset($value["amdYellow"]);
            }
            if(!$this->showGlaucoma){
                unset($value["glaucoma"]);
                unset($value["glaucomaRed"]);
                unset($value["glaucomaYellow"]);
            }
            
            if(!$this->showRef){
                unset($value["ref"]);
            }
            
            $csvArray[] = $value;
        }
        
        return $csvArray;
    }
    
    private function createHeaderCSVArray($startElems) {
        $csvArray = $startElems;
        if($this->showDR){
            $csvArray[] = 'DR count';
            $csvArray[] = 'Red DR';
            $csvArray[] = 'Yellow DR';
        }
        if($this->showAMD){
            $csvArray[] = 'AMD';
            $csvArray[] = 'Yellow AMD';
        }
        if($this->showGlaucoma){
            $csvArray[] = 'Glaucoma count';
            $csvArray[] = 'Red Glaucoma';
            $csvArray[] = 'Yellow Glaucoma';
        }
        $csvArray[] = 'Ungradable';
        if($this->showRef){
            $csvArray[] = 'Assessments';
        }
        return $csvArray;
    }
}
