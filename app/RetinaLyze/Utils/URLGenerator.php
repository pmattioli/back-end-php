<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Utils;

use RetinaLyze\Utils\Config;

/**
 * Description of URLGenerator
 *
 * @author mom
 */
class URLGenerator {
    /**
    * Returns a URL to the page where user was before this page
    * @param  string $exeption_path The path to go to if not referer is not your site
    * @return string                URL string
    */
   public static function backURL($exeption_path)
   {
       // Get base url to check if referer is the RetinaLyze domain.
       $config = Config::getConfig();
       $baseURL = $config['base_url'];
       // Get the referer, aka where user comes
       $http_ref = empty($_SERVER['HTTP_REFERER']) ? "" : $_SERVER['HTTP_REFERER'];
       $referer = htmlspecialchars($http_ref);
       // Has your site name in it
       if (strpos($referer, $baseURL) !== false) {
           return $referer;
       } else {
           // Where to go if the referer is not from retinalyze domain
           return $baseURL.$exeption_path;
       }
   }
}
