<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Statistics;

use RetinaLyze\Utils\AdminUtils;
use RetinaLyze\Analysis\ResultInterpreter;
use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of StatisticsHandler
 *
 * @author mom
 */

class StatisticsHandler {

    private $dbh;
    private $au;
    private $perMonth;
    private $showZeroColumns;

    function __construct($perMont = true, $showZeroColumns = false) {
        $this->dbh = new DatabaseHandler();
        $this->au = new AdminUtils();
        $this->perMonth = $perMont;
        $this->showZeroColumns = $showZeroColumns;
    }

    public function getCountryFromUserID($userID) {
        $userData = $this->dbh->getUserAdditionalInfoFromID($userID)->fetch_assoc();
        return $userData["country"];
    }

    public function getChainFromUserID($userID) { //
        $userData = $this->dbh->getUserInfoFromID($userID)->fetch_assoc();
        return $userData["chainID"];
    }
    
    public function showOverallStatisticsForCountry($country) {
        $html = "";
        $userCreationDBData = $this->dbh->getUserCreationStatisticsForCountry($country);
        $userCreationDBDataWithinMonth = $this->dbh->getUserCreationStatisticsForCountryWithinMonth($country);
        $html .= '<p>' . _('Total number of accounts') . ': ' . $userCreationDBData->num_rows . '</p>';
        $html .= '<p>' . _('New accounts this month') . ': ' . $userCreationDBDataWithinMonth->num_rows . '</p>';
        $dbData = $this->dbh->getStatisticsDataAllForCountryThisYear($country);
        $html .= $this->generateStatisticsHTMLFromDBData($dbData);
        return $html;
    }
    
    public function showOverallStatisticsForChain($chainID) {
        $html = "";
        $userCreationDBData = $this->dbh->getUserCreationStatisticsForChain($chainID);
        $userCreationDBDataWithinMonth = $this->dbh->getUserCreationStatisticsForChainWithinMonth($chainID);
        $html .= '<p>' . _('Total number of accounts') . ': ' . $userCreationDBData->num_rows . '</p>';
        $html .= '<p>' . _('New accounts this month') . ': ' . $userCreationDBDataWithinMonth->num_rows . '</p>';
        $dbData = $this->dbh->getStatisticsDataAllForChainThisYear($chainID);
        $html .= $this->generateStatisticsHTMLFromDBData($dbData);
        return $html;
    }

    public function showStatisticsForCountriesUsers($country) {
        $html = "";
        $allCountries = $this->au->getAllCountries();
        $countryName = $allCountries[$country];
        $html .= "<h2>" . $countryName . "</h2>";
        $html .= $this->au->printListOfChains("statistics", $country);
        $html .= "<h3>" . _('Users outside chain') . "</h3>";
        $usersData = $this->dbh->getUsersFromCountryOutsideChain($country);
        $html .= $this->generateHTMLFromData($usersData);

        return $html;
    }

    public function showStatisticsForChain($chainID) {
        $html = "";
        $chainName = $this->au->getChainNameFromID($chainID);
        $html .= "<h2>" . $chainName . "</h2>";
        $usersData = $this->dbh->getUsersFromChain($chainID);
        $html .= $this->generateHTMLFromData($usersData);
        
        return $html;
    }

    public function showStatisticsForUser($userID) {
        $html = "";
        $userDataDB = $this->dbh->getUserInfoFromID($userID);
        $userData = $userDataDB->fetch_assoc();
        $html .= "<h2>" . $userData["username"] . "</h2>";
        $userDataDB->data_seek(0);
        $html .= $this->generateHTMLFromData($userDataDB, true, true, true);
        return $html;
    }

    public function generateHTMLFromData($data, $hideUsername = false, $allData = false, $ignoreUserType = false) {
        $html = "";
        while ($user = $data->fetch_assoc()) {
            if (!($user["typeID"] == "2" || $ignoreUserType) && $user["disabled"] == "0") {
                continue;
            }
            $html .= "<div class='user_statistics'>";
            if (!$hideUsername) {
                $html .= "<h5 class='statistics_username'>" . $user["username"] . "</h5>";
            }else if($this->perMonth){
                $html .= '<p><a href="index.php?page=statistics&type=user&user=' . $user["userID"] . '&perDay=1">' . _('Click for statistics splitted per day') . '</a></p>';
            }else{
                $html .= '<p><a href="index.php?page=statistics&type=user&user=' . $user["userID"] . '">' . _('Click for statistics splitted per month') . '</a></p>';
            }
            if ($allData) {
                $statisticDataDB = $this->dbh->getStatisticsDataAllForUser($user["userID"]);
            } else {
                $statisticDataDB = $this->dbh->getStatisticsDataAllForUserThisYear($user["userID"]);
            }
            $html .= $this->generateStatisticsHTMLFromDBData($statisticDataDB, $allData);
            
            if (!$allData) {
                $html .= '<p><a href="index.php?page=statistics&type=user&user=' . $user["userID"] . '">' . _('Click for more statistics') . '</a></p>';            
            }
            $html .= "</div>";
            $html .= "<div class='gap statistics_end_of_table_gap'></div>";
            $html .= "<hr class='shadow'>";
            $html .= "<div class='gap statistics_end_of_table_gap'></div>";
        }
        return $html;
    }
    
    private function generateStatisticsHTMLFromDBData($dbData, $allData = false) {
        $html = "";
        $statistics = $this->generateStatisticsFromDBData($dbData);
            
        //Check if there is any screenings performed
        if(empty($statistics)){
            if ($allData) {
                $html .= "<p>" . _("No screenings performed") . "</p>";
            }else{
                $html .= "<p>" . _("No screenings performed within this year") . "</p>";
            }
        }else{
            ksort($statistics);

            $html .= $this->generateStatisticsHTMLForUserPerYear($statistics);
        }
        return $html;
    }


    private function generateStatisticsHTMLForUserPerYear($statistics) {
        $html = "";
        $lastYear = "";
        $lastMonth = "";
        
        $showGlaucoma = false;
        $showDR = false;
        $showAMD = false;
        $showRef = false;
        
        foreach($statistics as $row){
            if(!empty($row["dr"]) && $showDR == false){
                $showDR = true;
            }
            if(!empty($row["amd"]) && $showAMD == false){
                $showAMD = true;
            }
            if(!empty($row["glaucoma"]) && $showGlaucoma == false){
                $showGlaucoma = true;
            }
            if(!empty($row["ref"]) && $showRef == false){
                $showRef = true;
            }
        }
        
        if($this->showZeroColumns){
            $showGlaucoma = true;
            $showDR = true;
            $showAMD = true;
            $showRef = true;
        }
        
        foreach ($statistics as $date => $count) {
            $dateMonth = \DateTime::createFromFormat($this->getDateFormat(), $date);
            //Check if year have changed
            if ($lastYear != $dateMonth->format('Y')) {
                if($this->perMonth && $lastYear != ""){
                    $html .= "</tbody></table>";
                }
                $html .= "<div class='gap statistics_end_of_year_gap'></div>";
                $html .= "<p class='statistics_year'>" . $dateMonth->format('Y') . "</p>";
                $lastYear = $dateMonth->format('Y');
                if($this->perMonth){
                    $html .= $this->generateTableHeader($showGlaucoma, $showDR, $showAMD, $showRef);
                }
            }
            
            if (!$this->perMonth && $lastMonth != $dateMonth->format('m')) {
                if($lastMonth != ""){
                    $html .= "</tbody></table>";
                }
                $html .= "<div class='gap statistics_end_of_year_gap'></div>";
                $html .= "<p class='statistics_year'>" . $dateMonth->format('m') . "</p>";
                $lastMonth = $dateMonth->format('m');
                
                $html .= $this->generateTableHeader($showGlaucoma, $showDR, $showAMD, $showRef);                
            }
            
            $html .= $this->generateStatisticsHTMLForUserPerMonth($count, $dateMonth, $showGlaucoma, $showDR, $showAMD, $showRef);
        }
        $html .= "</tbody></table>";
        return $html;
    }
    
    private function generateStatisticsHTMLForUserPerMonth($count, $dateMonth, $showGlaucoma, $showDR, $showAMD, $showRef) {
        $html = "";        
        $html .= "<tr>";
        if($this->perMonth){
            $html .= "<td>" . $dateMonth->format('m') . "</td>";
        }else{
            $html .= "<td>" . $dateMonth->format('d') . "</td>";
        }
        if($showDR){
            $html .= "<td>" . $count["dr"] . "</td>";
        }
        if($showAMD){
            $html .= "<td>" . $count["amd"] . "</td>";
        }
        if($showGlaucoma){
            $html .= "<td>" . $count["glaucoma"] . "</td>";
        }
        $html .= "<td>" . $count["ungradable"] . "</td><td>" . ($count["amd"] + $count["dr"] + $count["ungradable"] + $count["glaucoma"]) . "</td>";
        if($showRef){
            $html .= "<td>" . $count["ref"] . "</td>";
        }
        if($showDR){
            $html .= "<td>" . $count["drYellow"] . "</td><td>" . $count["drRed"] . "</td>";
        }
        if($showAMD){
            $html .= "<td>" . $count["amdYellow"] . "</td>";
        }
        if($showGlaucoma){
            $html .= "<td>" . $count["glaucomaYellow"] . "</td><td>" . $count["glaucomaRed"] . "</td>";
        }
        
        $html .= "</tr>";
        
        return $html;
    }
    
    private function generateTableHeader($showGlaucoma, $showDR, $showAMD, $showRef) {
        $html = "";
        $html .= "<table class='data-table'>";
        $html .= "<thead><tr>";
        if($this->perMonth){
            $html .= "<th>" . _('Month') . "</th>";
        }else{
            $html .= "<th>" . _('Day') . "</th>";
        }
        
        if($showDR){
            $html .= "<th>" . _('DR count') . "</th>";
        }
        if($showAMD){
            $html .= "<th>" . _('AMD count') . "</th>";
        }
        if($showGlaucoma){
            $html .= "<th>" . _('Glaucoma count') . "</th>";
        }
        $html .= "<th>" . _('Ungradable') . "</th><th>" . _('Total') . "</th>";
        if($showRef){
            $html .= "<th>" . _('Assessments') . "</th>";
        }
        if($showDR){
            $html .= "<th>" . _('Yellow DR') . "</th><th>" . _('Red DR') . "</th>";
        }
        if($showAMD){
            $html .= "<th>" . _('Yellow AMD') . "</th>";
        }
        if($showGlaucoma){
            $html .= "<th>" . _('Yellow Glaucoma') . "</th><th>" . _('Red Glaucoma') . "</th>";
        }
        
        $html .= "</tr></thead>";
        $html .= "<tbody>";
        return $html;
    }

    function insertResultStatisticsData($statistics, $imgInfo, $userSettings) {
        $format = $this->getDateFormat();
        $result = ResultInterpreter::getResultColorsStandard($imgInfo, $userSettings);
        if ($result['dr'] == "ungradable" || $result['amd'] == "ungradable") {
            $time = \DateTime::createFromFormat('Y-m-d H:i:s', $imgInfo["timeAnalyzedDR"]);
            if($time === false){
                $time = \DateTime::createFromFormat('Y-m-d H:i:s', $imgInfo["timeAnalyzedAMD"]);
            }
            if($time === false){
                $time = \DateTime::createFromFormat('Y-m-d H:i:s', $imgInfo["timeGlaucomaStarted"]);
            }
            if($time === false){
                $time = \DateTime::createFromFormat('Y-m-d H:i:s', $imgInfo["timeUploaded"]);
            }
            $statistics[$time->format($format)]["ungradable"] ++;
        }else{
            if($result['dr'] !== NULL){
                $DRtime = \DateTime::createFromFormat('Y-m-d H:i:s', $imgInfo["timeAnalyzedDR"]);
                $statistics[$DRtime->format($format)]["dr"]++;
                if ($result['dr'] == "red") {
                    $statistics[$DRtime->format($format)]["drRed"] ++;
                } elseif ($result['dr'] == "yellow") {
                    $statistics[$DRtime->format($format)]["drYellow"] ++;
                }
            }
            if($result['amd'] !== NULL){
                $AMDtime = \DateTime::createFromFormat('Y-m-d H:i:s', $imgInfo["timeAnalyzedAMD"]);
                $statistics[$AMDtime->format($format)]["amd"]++;
                if ($result['amd'] == "yellow") {
                    $statistics[$AMDtime->format($format)]["amdYellow"] ++;
                }
            }
        }
        
        if ($result['glaucoma'] == "ungradable") {
            $time = \DateTime::createFromFormat('Y-m-d H:i:s', $imgInfo["timeAnalyzedDR"]);
            if($time === false){
                $time = \DateTime::createFromFormat('Y-m-d H:i:s', $imgInfo["timeAnalyzedAMD"]);
            }
            if($time === false){
                $time = \DateTime::createFromFormat('Y-m-d H:i:s', $imgInfo["timeGlaucomaStarted"]);
            }
            if($time === false){
                $time = \DateTime::createFromFormat('Y-m-d H:i:s', $imgInfo["timeUploaded"]);
            }
            $statistics[$time->format($format)]["ungradable"] ++;
        }else{
            if($result['glaucoma'] !== NULL && $result['glaucoma'] != "ungradable"){
                $glaucomaTime = \DateTime::createFromFormat('Y-m-d H:i:s', $imgInfo["timeGlaucomaStarted"]);
                if($glaucomaTime !== false){
                    $statistics[$glaucomaTime->format($format)]["glaucoma"]++;
                    if ($result['glaucoma'] == "red") {
                        $statistics[$glaucomaTime->format($format)]["glaucomaRed"] ++;
                    } elseif ($result['glaucoma'] == "yellow") {
                        $statistics[$glaucomaTime->format($format)]["glaucomaYellow"] ++;
                    }
                }
            }
        }
        return $statistics;
    }

    function insertRefStatisticsData($statistics, $refTime) {
        $format = $this->getDateFormat();
        $UploadMonthYear = date($format, $refTime);
        $statistics[$UploadMonthYear]["ref"] ++;
        return $statistics;
    }

    public function generateStatisticsFromDBData($statisticDataDB) {
        $firstRow = $statisticDataDB->fetch_assoc();
        if($firstRow === NULL){
            return array();
        }
        
        //Get additional user info (used for checking offset)
        $userInfoDB = $this->dbh->getUserSettingsFromID($firstRow['userID']);
        $userInfo = $userInfoDB->fetch_assoc();
        
        $statistics = $this->generateArrayWithDates($firstRow["timeUploaded"]);
        $statisticDataDB->data_seek(0);
        while ($statisticData = $statisticDataDB->fetch_assoc()) {
            
            //Result related
            $statistics = $this->insertResultStatisticsData($statistics, $statisticData, $userInfo);

            //Referral related
            $refTime = strtotime($statisticData["refTime"]);
            if ($refTime != false) {
                $statistics = $this->insertRefStatisticsData($statistics, $refTime);
            }
        }
        return $statistics;
    }
    
    function generateArrayWithDates($beginDate){
        $array = array();
        $firstDate = \DateTime::createFromFormat('Y-m-d H:i:s', $beginDate);
        $dateFirstMonth = \DateTime::createFromFormat('Y-m-d', $firstDate->format('Y-m') . '-01');
        $end = new \DateTime('tomorrow');
        if($this->perMonth){
            $i = new \DateInterval('P1M');
        }else{
            $i = new \DateInterval('P1D');
        }        
        $period = new \DatePeriod($dateFirstMonth, $i, $end);
        foreach ($period as $day) {
            $date = $day->format($this->getDateFormat());
            $array[$date] = array("dr" => 0,
                                "drRed" => 0,
                                "drYellow" => 0,
                                "amd" => 0,
                                "amdYellow" => 0,
                                "glaucoma" => 0,
                                "glaucomaRed" => 0,
                                "glaucomaYellow" => 0,
                                "ungradable" => 0,
                                "ref" => 0,);
        }
        return $array;
    }
    
    private function getDateFormat(){
        if($this->perMonth){
            return "Y-m";
        }else{
            return "Y-m-d";
        }
    }

}
