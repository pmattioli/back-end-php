<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Location;

use GeoIp2\WebService\Client;

/**
 * Description of GeoIP
 *
 * @author mom
 */
class GeoIP {
    public static function getCountry($ip) {
        try{
            $client = new Client(120045, 'fYdw9rAaDyFL', ['en'], ['connectTimeout' => 2]);
            $record = $client->country($ip);
            return $record->country->isoCode;
        } catch (\GeoIp2\Exception\AddressNotFoundException $ex) {
            return "DK";
        } catch (\GeoIp2\Exception\IpAddressNotFoundException $ex) {
            return "DK";
        } catch (\Exception $ex) {
            error_log("Error: Could not get country via GeoIP'ing" . $ex);
            return "DK";
        }
    }
    
    public static function getSuggestedTimezone($ip) {
        try{
            $client = new Client(120045, 'fYdw9rAaDyFL', ['en'], ['connectTimeout' => 2]);
            $record = $client->city($ip);
            return $record->location->timeZone;
        } catch (\GeoIp2\Exception\AddressNotFoundException $ex) {
            return NULL;
        } catch (\GeoIp2\Exception\IpAddressNotFoundException $ex) {
            return NULL;
        } catch (\Exception $ex) {
            error_log("Error: Could not get country via GeoIP'ing" . $ex);
            return NULL;
        }
        
    }
    
    public static function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}
