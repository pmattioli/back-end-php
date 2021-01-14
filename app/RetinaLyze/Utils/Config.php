<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Utils;

/**
 * Description of Config
 *
 * @author mom
 */
class Config {
    public static function getConfig() {
        return include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'RetinaLyze_ConfigFiles/config.php';
    }
}
