<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Search;

use RetinaLyze\Authentication\CheckAuthentication;
use RetinaLyze\Database\DatabaseHandler;
use RetinaLyze\Login\LoginChecker;

/**
 * Description of SearchImages
 *
 * @author mom
 */
class SearchImages {
    
    private $dbh;
    private $loginChecker;    
    
    function __construct() {
        $this->dbh = new DatabaseHandler();
        $this->loginChecker = new LoginChecker(true);
    }
    
    public function searchWithinCurrentUserData($searchTerm) {
        return $this->dbh->getAnalyzedImgInfoSearch($this->loginChecker->getUserID(), $searchTerm);
    }
    
    public function searchWithinAllAllowedUserData($searchTerm){
        $authentication = new CheckAuthentication();
        $usersqllist = $authentication->getSQLListOfUsersCurrentUserHaveAccessTo();
        return $this->dbh->getAnalyzedImgInfoSearchInUsers($searchTerm, $usersqllist);
    }
}
