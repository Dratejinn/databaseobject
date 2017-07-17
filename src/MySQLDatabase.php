<?php

declare(strict_types = 1);

namespace DatabaseObject;

use DatabaseObject\Interfaces\{IUserCredentials, IConnectionDetails};

class MySQLDatabase extends Abstracts\ADatabase {

    /**
     * The default database name
     * @var null|string
     */
    protected static $_DefaultDatabase = NULL;

    /**
     * The charset for the database
     * @var string
     */
    protected $_charSet = 'utf8';

    /**
     * the database name
     * @var null|string
     */
    protected $_database = NULL;

    /**
     * MySQLDatabase constructor.
     * @param \DatabaseObject\Interfaces\IUserCredentials|NULL $userCredentials
     * @param \DatabaseObject\Interfaces\IConnectionDetails|NULL $connectionDetails
     * @param string|NULL $database
     */
    public function __construct(IUserCredentials $userCredentials = NULL, IConnectionDetails $connectionDetails = NULL, string $database = NULL) {
        parent::__construct($userCredentials, $connectionDetails);
        $this->_database = $database;
    }

    /**
     * Used to get the dsn required for constructing the \PDO connection
     * @return string
     * @throws \Exception
     */
    protected function _getDsn() : string {
        $dsn = parent::_getDsn();
        $database = $this->_database ?: static::$_DefaultDatabase;
        if ($database === NULL) {
            throw new \Exception('No Database provided!');
        }
        $dsn .= 'dbname=' . $database;

        if ($this->_charSet !== NULL) {
            $dsn .= ';charset=' . $this->_charSet;
        }

        return $dsn;
    }

    /**
     *
     * @param string|NULL $database
     * @return \PDO
     */
    public function connect(string $database = NULL) : \PDO {
        $this->_database = $database;
        return parent::connect();
    }

    /**
     * Set the default database name
     * @param string $databaseName
     */
    public static function SetDefaultDatabase(string $databaseName) {
        static::$_DefaultDatabase = $databaseName;
    }

    /**
     * Get the default database name
     * @return string
     */
    public static function GetDefaultDatabase() : string {
        return static::$_DefaultDatabase;
    }

    /**
     * @inheritdoc
     */
    protected function _getURI() : string {
        return 'mysql';
    }
}
