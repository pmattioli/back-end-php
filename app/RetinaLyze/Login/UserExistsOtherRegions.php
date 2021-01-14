<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Login;

use RetinaLyze\Utils\Config;
/**
 * Description of UserExistsOtherRegions
 *
 * @author mom
 */
class UserExistsOtherRegions {
    public static function getOtherRegionWhereUserExists($username) {
        $config = Config::getConfig();
        
        //Setup POST request
        $data = array('api' => 'Qs4Gw*NwP2VXcj3vxXQXra35p3U*X4Hd', 'username' => $username);
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context  = stream_context_create($options);
        foreach($config['other_regions_urls'] as $regionURL){
            $result = file_get_contents('https://' . $regionURL . '/api/v0/userExists.php', false, $context);
            if($result === 'true'){
                return $regionURL;
            }
        }
        return false;
    }
}
