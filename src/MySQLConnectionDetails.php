<?php

declare(strict_types = 1);

namespace DatabaseObject;

use DatabaseObject\Traits\TConnectionDetails;
use DatabaseObject\Interfaces\IConnectionDetails;

class MySQLConnectionDetails implements IConnectionDetails {

    use TConnectionDetails;

    const MODE_UNIXSOCKET = 0x01;
    const MODE_NETWORKCONNECTION = 0x03;

    /**
     * the current connection mode
     * @var int
     */
    protected $_mode = NULL;

    /**
     * MySQLConnectionDetails constructor.
     */
    public function __construct() {
        $this->setNetworkConnection('localhost', '3306');
    }

    /**
     * returns the current mode for the connection
     * @return int
     */
    public function getMode() : int {
        return $this->_mode;
    }

    /**
     * Set the connection mode to a network connection
     * @param string $host
     * @param string $port
     */
    public function setNetworkConnection(string $host, string $port) {
        $this->_options = [
            'host' => $host,
            'port' => $port
        ];
        $this->_mode = self::MODE_NETWORKCONNECTION;
    }

    /**
     * Set the connection mode to unix socket
     * @param string $unixSocket
     */
    public function setUnixSocketConnection(string $unixSocket) {
        $this->_options = [
            'unix_socket' => $unixSocket
        ];
        $this->_mode = self::MODE_UNIXSOCKET;
    }

}
