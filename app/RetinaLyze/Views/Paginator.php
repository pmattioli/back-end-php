<?php

/*
 * All rights reserved RetinaLyze System A/S, Denmark
 */

namespace RetinaLyze\Views;

use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of Paginator
 *
 * @author mom
 */
class Paginator {

    private $dbh;
    private $pages;
    private $start;
    private $end;
    private $page;
    private $total;
    
    
    
    function __construct($dbh) {
        if ($dbh == null) {
            $this->dbh = new DatabaseHandler();
        } else {
            $this->dbh = $dbh;
        }
    }

    public function getData($page, $userID) {
        try {
            $this->page = $page;
            // Find out how many items are in the table
            $this->total = $this->dbh->getAnalyzedImgInfoCount($userID)->fetch_assoc()['COUNT'];
            // How many items to list per page
            $limit = 100;

            // How many pages will there be
            $this->pages = ceil($this->total / $limit);

            // Calculate the offset for the query
            $offset = ($this->page - 1) * $limit;

            // Some information to display to the user
            $this->start = $offset + 1;
            $this->end = min(($offset + $limit), $this->total);


            // Prepare the paged query
            return $this->dbh->getAnalyzedImgInfoPaginated($userID, $limit, $offset);

        } catch (\Exception $e) {
            error_log('Could not perform get data pagination: ' . $e->getMessage());
        }
    }
    
    public function getDataGESB($page) {
        try {
            $this->page = $page;
            // Find out how many items are in the table
            $this->total = $this->dbh->getReferralWithResponseGESBCount()->fetch_assoc()['COUNT'];
            // How many items to list per page
            $limit = 100;

            // How many pages will there be
            $this->pages = ceil($this->total / $limit);

            // Calculate the offset for the query
            $offset = ($this->page - 1) * $limit;

            // Some information to display to the user
            $this->start = $offset + 1;
            $this->end = min(($offset + $limit), $this->total);


            // Prepare the paged query
            return $this->dbh->getReferralWithResponseGESBPaginated($limit, $offset);

        } catch (\Exception $e) {
            error_log('Could not perform get data pagination: ' . $e->getMessage());
        }
    }
    
    public function getDataLESB($page, $lbregion) {
        try {
            $this->page = $page;
            // Find out how many items are in the table
            $this->total = $this->dbh->getReferralWithResponseLESBCount($lbregion)->fetch_assoc()['COUNT'];
            // How many items to list per page
            $limit = 100;

            // How many pages will there be
            $this->pages = ceil($this->total / $limit);

            // Calculate the offset for the query
            $offset = ($this->page - 1) * $limit;

            // Some information to display to the user
            $this->start = $offset + 1;
            $this->end = min(($offset + $limit), $this->total);


            // Prepare the paged query
            return $this->dbh->getReferralWithResponseLESBPaginated($lbregion, $limit, $offset);

        } catch (\Exception $e) {
            error_log('Could not perform get data pagination: ' . $e->getMessage());
        }
    }

    public function getNav($currentLink) {
        // The "back" link
        $prevlink = ($this->page > 1) ? '<a href="' . $currentLink . '&pageno=1">&laquo;</a> <a href="' . $currentLink . '&pageno=' . ($this->page - 1) . '">&lsaquo;</a>' : '<span class="disabled">&laquo;</span> <span class="disabled">&lsaquo;</span>';

        // The "forward" link
        $nextlink = ($this->page < $this->pages) ? '<a href="' . $currentLink . '&pageno=' . ($this->page + 1) . '">&rsaquo;</a> <a href="' . $currentLink . '&pageno=' . $this->pages . '">&raquo;</a>' : '<span class="disabled">&rsaquo;</span> <span class="disabled">&raquo;</span>';

        // Display the paging information
        $text = sprintf(_('Page %d of %d pages, displaying %d-%d of %d results'), $this->page, $this->pages, $this->start, $this->end, $this->total);
        return '<div id="paging"><p>' . $prevlink . ' ' . $text . ' ' . $nextlink . ' </p></div>';
    }

}
