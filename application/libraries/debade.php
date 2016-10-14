<?php

class DeBaDe
{
    private static $_QUEUES = [];
    public static function of($name) {
        if (!isset(self::$_QUEUES[$name])) {
            self::$_QUEUES[$name] = new DeBaDe($name);
        }

        return self::$_QUEUES[$name];
    }

    private $_name;
    private $_sock;
    private $_queue;

    public function __construct($name) {
        try {
            $this->_name = $name;

            $queues = Config::get('debade.queues');
            if (isset($queues[$name])) {
                $options = $queues[$name];
                $sock = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_PUSH);
                $sock->connect($options['addr']);
                $this->_sock = $sock;
                
                $this->_queue = $options['queue'];
            }
        } catch (\Exception $e) {
            // DO NOTHING
        }
    }

    public function push($rmsg, $routing_key=null) {
        if (!$this->_sock) {
            return;
        }
        
        $msg = [
            'queue' => $this->_queue,
            'data' => $rmsg,
        ];
        
        if ($routing_key) {
            $msg['routing'] = $routing_key;
        }

        $this->_sock->send(json_encode($msg, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
    }

}
