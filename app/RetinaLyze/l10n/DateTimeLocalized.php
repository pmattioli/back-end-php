<?php namespace RetinaLyze\l10n;

/*
 * All Rights Reserved RetinaLyze System.
 */

/**
 * DateTimeLocalized generates localized date and time string.
 *
 * @author mom
 */
class DateTimeLocalized {
    
    /**
     * 
     * @param mixed $time
     * @param string $locale
     * @param string $tz
     * @param boolean $intTimestamp time parsed as int timestamp and not DateTime object.
     * @return string
     */
    
    public static function getShortDateTime($time, $locale, $tz, $intTimestamp = false){
        $fmt = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT, "UTC");
        $fmt->setTimeZone($tz);
        if($intTimestamp){
            $humanReadableTime = $fmt->format($time);
        }else{
            $humanReadableTime = $fmt->format(strtotime($time));
        }
        if($humanReadableTime === false){
            error_log($fmt->getErrorMessage());
            return "";
        }else{
            return $humanReadableTime;
        }
    }
}
