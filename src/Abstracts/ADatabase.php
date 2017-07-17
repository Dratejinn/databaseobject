<?php

declare(strict_types = 1);

namespace Jarvis\Plugin\CorveeBot\DatabaseObjects\Abstracts;

use DatabaseObject\Interfaces\{IUserCredentials, IConnectionDetails};

abstract class ADatabase {

    protected static $_DefaultDatabase = NULL;

    private static $_DefaultUserCredentials = NULL;
    private static $_DefaultConnectionDetails = NULL;

    protected $_userCredentials = NULL;
    protected $_connectionDetails = NULL;

    protected $_connection = NULL;
    protected $_options = [];

    public function __construct(IUserCredentials $userCredentials = NULL, IConnectionDetails $connectionDetails = NULL) {
        $this->_userCredentials = $userCredentials ?? self::GetDefaultUserCredentials();
        if ($this->_userCredentials === NULL) {
            throw new \Exception('no user credentials provided!');
        }
        $this->_connectionDetails = $connectionDetails ?? self::GetDefaultConnectionDetails();
        if ($this->_connectionDetails === NULL) {
            throw new \Exception('no connection details provided!');
        }
    }

    public function connect() : \PDO {
        if ($this->_connection === NULL) {
            $dsn = $this->_getDsn();
            try {
                $this->_connection = new \PDO($dsn, $this->_userCredentials->getUsername(), $this->_userCredentials->getPassword(), array_merge($this->_options, $this->getPDODriverOptions()));
            } catch (\PDOException $exception) {
                throw new \InvalidArgumentException(sprintf(
                    'There was a problem connecting to the database: %s',
                    $exception->getMessage()
                ));
            }
        }
        return $this->_connection;
    }

    public function disconnect() {
        $this->_connection = NULL;
    }

    protected function _getDsn() : string {
        $dsn = $this->_getURI();
        if (strpos($dsn, ':') === FALSE) {
            $dsn .= ':';
        }
        $dsn .= $this->_connectionDetails->getConnectionDetailsAsDsnPart();

        return $dsn;
    }

    public function getPDODriverOptions() : array {
        return [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];
    }

    public static function SetDefaultUserCredentials(IUserCredentials $userCredentials) {
        self::$_DefaultUserCredentials = $userCredentials;
    }

    public static function GetDefaultUserCredentials() {
        return self::$_DefaultUserCredentials;
    }

    public static function SetDefaultConnectionDetails(IConnectionDetails $connectionDetails) {
        self::$_DefaultConnectionDetails = $connectionDetails;
    }

    public static function GetDefaultConnectionDetails() {
        return self::$_DefaultConnectionDetails;
    }

    abstract protected function _getURI() : string;
}
