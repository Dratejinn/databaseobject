<?php

declare(strict_types = 1);

namespace DatabaseObject;

use DatabaseObject\Traits\TConnectionDetails;
use DatabaseObject\Interfaces\IConnectionDetails;

class MySQLConnectionDetails implements IConnectionDetails {

    use TConnectionDetails;

    const MODE_UNIXSOCKET = 0x01;
    const MODE_NETWORKCONNECTION = 0x03;

    protected $_mode = NULL;

    public function __construct() {
        $this->setNetworkConnection('localhost', '3306');
    }

    public function getMode() : int {
        return $this->_mode;
    }

    public function setNetworkConnection(string $host, string $port) {
        $this->_options = [
            'host' => $host,
            'port' => $port
        ];
        $this->_mode = self::MODE_NETWORKCONNECTION;
    }

    public function setUnixSocketConnection(string $unixSocket) {
        $this->_options = [
            'unix_socket' => $unixSocket
        ];
        $this->_mode = self::MODE_UNIXSOCKET;
    }

}
