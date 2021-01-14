<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Analysis;

use RetinaLyze\Utils\Config;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Description of AnalyzeRPCRequester
 *
 * @author mom
 */
class AnalyzeQueueRequester {

    private $connection;
    private $channel;
    private $response;
    private $corr_id;

    public function __construct() {
        $config = Config::getConfig();
        $this->connection = new AMQPStreamConnection($config['worker_url'], $config["worker_port"], $config['worker_username'], $config['worker_password'], 'analyze', false, 'AMQPLAIN', null, 'en_US', 700, 700, null, false, 300);
        $this->channel = $this->connection->channel();
    }

    public function call($n) {
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
                (string) $n, array('rpc' => 'false')
        );
        $this->channel->basic_publish($msg, '', 'analyze_requests');
    }

}
