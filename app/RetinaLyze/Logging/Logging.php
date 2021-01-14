<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Logging;

use Monolog\Logger;
use Monolog\Handler\RedisHandler;
use Predis\Client;


/**
 * Description of newPHPClass
 *
 * @author mom
 */
class Logging {
    function __construct() {
        try {
            // Create the logger
            $logger = new Logger('WebAppLogger');
            // Now add some handlers

            $handler = new RedisHandler(new Client(), "test");
            $handler->setPersistent(true);

            $logger->pushHandler($handler, Logger::DEBUG);

            // You can now use your logger
            $logger->info('My logger is now ready');
        } catch (\Exception $exc) {
            error_log($exc->getTraceAsString());
        }
    }
}
