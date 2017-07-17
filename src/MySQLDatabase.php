<?php

declare(strict_types = 1);

namespace DatabaseObject;

use DatabaseObject\Interfaces\{IUserCredentials, IConnectionDetails};

class MySQLDatabase extends Abstracts\ADatabase {

    protected static $_DefaultDatabase = NULL;

    protected $_charSet = 'utf8';

    protected $_database = NULL;

    public function __construct(IUserCredentials $userCredentials = NULL, IConnectionDetails $connectionDetails = NULL, string $database = NULL) {
        parent::__construct($userCredentials, $connectionDetails);
        $this->_database = $database;
    }

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

    public function connect(string $database = NULL) : \PDO {
        if ($database !== NULL) {
            $temp = $this->_database;
            $this->_database = $database;
        }
        $pdo = parent::connect();
        if ($database !== NULL) {
            $this->_database = $temp;
        }
        return $pdo;
    }

    public static function SetDefaultDatabase(string $DatabaseName) {
        static::$_DefaultDatabase = $DatabaseName;
    }

    public static function GetDefaultDatabase() : string {
        return static::$_DefaultDatabase;
    }

    protected function _getURI() : string {
        return 'mysql';
    }
}
