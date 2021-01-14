<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Utils;

use RetinaLyze\Location\Countries;
use RetinaLyze\Statistics\StatisticsHandler;
use RetinaLyze\Database\DatabaseHandler;

class AdminUtils {

    private $dbh;

    function __construct() {
        $this->dbh = new DatabaseHandler();
    }

    public function printCurrentCountries($page, $showZeroColumns = false) {
        $html = "";
        $countriesActive = $this->getCurrentlyUsedCountries();
        $allCountries = $this->getAllCountries();
        $html .= "<h2>" . _('Choose country') . "</h2>";
        $sh = new StatisticsHandler(true, $showZeroColumns);
        //Create array that make it possible to sort by country name
        $countriesActiveWithName = array();
        foreach ($countriesActive as $countryActive){
            $countriesActiveWithName[$allCountries[$countryActive]] = $countryActive;
        }
        ksort($countriesActiveWithName);
        foreach ($countriesActiveWithName as $countryActive) {
            if(array_key_exists($countryActive, $allCountries)){
                $countryName = $allCountries[$countryActive];
                if($page == 'statistics'){
                    $html .= "<h5><a href='index.php?page=statistics&type=country&amp;country=" . $countryActive . "'>" . $countryName . "</a></h5>";
                    $html .= $sh->showOverallStatisticsForCountry($countryActive);
                    $html .= "<div class='gap statistics_end_of_table_gap'></div>";
                    $html .= "<hr class='shadow'>";
                    $html .= "<div class='gap statistics_end_of_table_gap'></div>";
                }else{
                    $html .= "<h5><a href='index.php?page=" . $page . "&amp;country=" . $countryActive . "'>" . $countryName . "</a></h5>";
                }
            }
        }
        return $html;
    }

    public function printListOfChains($page, $country) {
        $html = "";
        $html .= "<h3>" . _('Chains') . "</h3>";
        $chains = $this->getChainsFromCountry($country);
        $sh = new StatisticsHandler();
        foreach ($chains as $chain) {
            if($page == 'statistics'){
                $html .= "<h5><a href='index.php?page=statistics&type=chain&amp;country=" . $country . "&amp;chain=" . $chain["id"] . "'>" . $chain["name"] . " </a></h5>";
                $html .= $sh->showOverallStatisticsForChain($chain["id"]);
                $html .= "<div class='gap statistics_end_of_table_gap'></div>";
                $html .= "<hr class='shadow'>";
                $html .= "<div class='gap statistics_end_of_table_gap'></div>";
            }else{
                $html .= "<h5><a href='index.php?page=" . $page . "&amp;country=" . $country . "&amp;chain=" . $chain["id"] . "'>" . $chain["name"] . " </a></h5>";
            }
        }
        return $html;
    }

    public function printListOfAllChains($page) {
        $html = "";
        $html .= "<h3>" . _('Chains') . "</h3>";
        $chains = $this->getAllChains();
        foreach ($chains as $chain) {
            $html .= "<p><a href='index.php?page=" . $page . "&amp;chain=" . $chain["id"] . "'>" . $chain["name"] . " </a></p>";
        }
        return $html;
    }

    public function getAllCountries() {
        return Countries::getContryArray();
    }

    public function getChainNameFromID($chainID) {
        $chainData = $this->dbh->getChainNameFromID($chainID)->fetch_assoc();
        return $chainData["name"];
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

    public function getAllChains() {
        $chaindb = $this->dbh->getAllChains();
        $chains = array();
        while ($chain = $chaindb->fetch_assoc()) {
            $chains[] = array("id" => $chain["chain_id"], "name" => $chain["name"]);
        }
        return $chains;
    }

}