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
class AnalyzeRPCRequester {

    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;
    private $running = false;

    public function __construct() {
        $config = Config::getConfig();
        $this->connection = new AMQPStreamConnection($config['worker_url'], $config["worker_port"], $config['worker_username'], $config['worker_password'], 'analyze', false, 'AMQPLAIN', null, 'en_US', 700, 700, null, false, 300);
        $this->channel = $this->connection->channel();
        list($this->callback_queue,, ) = $this->channel->queue_declare("analyze_rpc_queue_" . uniqid(), false, false, true, false);
        $this->channel->basic_consume($this->callback_queue, '', false, false, false, false, array($this, 'on_response'));
    }

    public function on_response($rep) {
        if ($rep->get('correlation_id') == $this->corr_id) {
            //Check if its an ack of a comsumer starting the analyzes
            if ($rep->body == "RUNNING") {
                $this->running = true;
            } else {
                $this->response = $rep->body;
            }
        }
    }

    public function call($n) {
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
                (string) $n, array('rpc' => 'false', 'correlation_id' => $this->corr_id,
            'reply_to' => $this->callback_queue)
        );
        $this->channel->basic_publish($msg, '', 'analyze_requests');
        while (!$this->running) {
            //Wait for answer. Timeout is set to 10 min (600 seconds)
            $this->channel->wait(null, false, 600);
        }
        while (!$this->response) {
            //Wait for answer. Timeout is set to 2 min (120 seconds)
            $this->channel->wait(null, false, 120);
        }
        return $this->response;
    }

}
