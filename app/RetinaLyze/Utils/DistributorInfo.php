<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Utils;

use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of DistributorInfo
 *
 * @author mom
 */
class DistributorInfo {
    private $dbh;
    
    
    function __construct() {
        $this->dbh = new DatabaseHandler();
    }
    
    public function getDistributorID($country) {
        $distributorIDDB = $this->dbh->getDistributorFromCountryCode($country);
        if($distributorIDDB != null && $distributorIDDB->num_rows != 0){
            $distributorIDArray = $distributorIDDB->fetch_assoc();
            $distributorID = $distributorIDArray["distributorID"];
        }else{
            $defaultDistributorIDDB = $this->dbh->getDistributorFromCountryCode("DK");
            $defaultDistributorIDArray = $defaultDistributorIDDB->fetch_assoc();
            $distributorID = $defaultDistributorIDArray["distributorID"];
        }
        return $distributorID;
    }
    
    public function getDistributorInfo($distributorID) {
        $distributorInfoDB = $this->dbh->getDistributorInfoFromDistributorID($distributorID);
        return $distributorInfoDB->fetch_assoc();
    }
    
    public function getAllDistributorsInfo() {
        $countriesDB = $this->dbh->getDistributorCountries();
        $returnArray = array();
        while($country = $countriesDB->fetch_assoc()){
            $distributorInfoDB = $this->dbh->getDistributorInfoFromDistributorID($country["distributorID"]);
            $returnArray[$country["countryCode"]] = $distributorInfoDB->fetch_assoc();
        }
        return $returnArray;
    }
}
