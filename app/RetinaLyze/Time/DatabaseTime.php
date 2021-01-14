<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Time;

/**
 * Description of DatabaseTime
 *
 * @author mom
 */
class DatabaseTime {
    /**
     * Returns the current time in a DB formatted string.
     * @return string
     */
    public static function getCurrentTime() {
        $phpdate = new \DateTime();
        return $phpdate->format('Y-m-d H:i:s');
    }
}
