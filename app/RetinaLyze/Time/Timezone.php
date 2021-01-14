<?php

namespace RetinaLyze\Time;

/*
 * All Rights Reserved RetinaLyze System.
 */

/**
 * Timezone handle timezones.
 *
 * @author mom
 */
class Timezone {

    /**
     * Returns all available timezones Key values is php timezone names, and values is human readable names
     * @return string[] 
     */
    public static function getHumanReadableTimezones() {
        $regions = array(
            'Africa' => \DateTimeZone::AFRICA,
            'America' => \DateTimeZone::AMERICA,
            'Antarctica' => \DateTimeZone::ANTARCTICA,
            'Asia' => \DateTimeZone::ASIA,
            'Atlantic' => \DateTimeZone::ATLANTIC,
            'Europe' => \DateTimeZone::EUROPE,
            'Indian' => \DateTimeZone::INDIAN,
            'Pacific' => \DateTimeZone::PACIFIC
        );
        $timezones = array();
        foreach ($regions as $name => $mask) {
            $zones = \DateTimeZone::listIdentifiers($mask);
            foreach ($zones as $timezone) {
                // Lets sample the time there right now
                $time = new \DateTime(NULL, new \DateTimeZone($timezone));
                // Us dumb Americans can't handle millitary time
                $ampm = $time->format('H') > 12 ? ' (' . $time->format('g:i a') . ')' : '';
                // Remove region name and add a sample time
                $timezones[$name][$timezone] = substr($timezone, strlen($name) + 1) . ' - ' . $time->format('H:i') . $ampm;
            }
        }
        return $timezones;
    }

}
