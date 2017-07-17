<?php

declare(strict_types = 1);

namespace DatabaseObject\Abstracts;

use DatabaseObject\Interfaces\{IUserCredentials, IConnectionDetails};

abstract class ADatabase {

    /**
     * @var null|string
     */
    protected static $_DefaultDatabase = NULL;

    /**
     * @var null|\DatabaseObject\Interfaces\IUserCredentials
     */
    private static $_DefaultUserCredentials = NULL;

    /**
     * @var null|\DatabaseObject\Interfaces\IConnectionDetails
     */
    private static $_DefaultConnectionDetails = NULL;

    /**
     * @var \DatabaseObject\Interfaces\IUserCredentials|null
     */
    protected $_userCredentials = NULL;

    /**
     * @var \DatabaseObject\Interfaces\IConnectionDetails|null
     */
    protected $_connectionDetails = NULL;

    /**
     * @var null|\PDO
     */
    protected $_connection = NULL;

    /**
     * @var array
     */
    protected $_options = [];

    /**
     * ADatabase constructor.
     * @param \DatabaseObject\Interfaces\IUserCredentials|NULL $userCredentials
     * @param \DatabaseObject\Interfaces\IConnectionDetails|NULL $connectionDetails
     * @throws \Exception
     */
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

    /**
     * Tries to connect to the database. Returns the \PDO object on success
     * @return \PDO
     */
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

    /**
     * clears the current connection
     */
    public function disconnect() {
        $this->_connection = NULL;
    }

    /**
     * Get the dsn required to connect to the database
     * @return string
     */
    protected function _getDsn() : string {
        $dsn = $this->_getURI();
        if (strpos($dsn, ':') === FALSE) {
            $dsn .= ':';
        }
        $dsn .= $this->_connectionDetails->getConnectionDetailsAsDsnPart();

        return $dsn;
    }

    /**
     * Returns an array of pdo driver options
     * @return array
     */
    public function getPDODriverOptions() : array {
        return [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];
    }

    /**
     * Set the default user credentials
     * @param \DatabaseObject\Interfaces\IUserCredentials $userCredentials
     */
    public static function SetDefaultUserCredentials(IUserCredentials $userCredentials) {
        self::$_DefaultUserCredentials = $userCredentials;
    }

    /**
     * Used to get the default user credentials
     * @return \DatabaseObject\Interfaces\IUserCredentials|null
     */
    public static function GetDefaultUserCredentials() {
        return self::$_DefaultUserCredentials;
    }

    /**
     * Set the default connection details
     * @param \DatabaseObject\Interfaces\IConnectionDetails $connectionDetails
     */
    public static function SetDefaultConnectionDetails(IConnectionDetails $connectionDetails) {
        self::$_DefaultConnectionDetails = $connectionDetails;
    }

    /**
     * Used to get the default connection details
     * @return \DatabaseObject\Interfaces\IConnectionDetails|null
     */
    public static function GetDefaultConnectionDetails() {
        return self::$_DefaultConnectionDetails;
    }

    /**
     * Must be implemented by the extending class to define the \PDO uri
     * @return string
     */
    abstract protected function _getURI() : string;
}
