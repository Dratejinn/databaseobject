<?php

namespace DatabaseObject\Abstracts;

use DatabaseObject\DatabaseObjectDefinition;

abstract class ADatabaseObject {

    const RETRIEVAL_OBJECT      = 0x01;
    const RETRIEVAL_STATEMENT   = 0x03;
    const RETRIEVAL_COUNT       = 0x05;

    /**
     * @var \DatabaseObject\Abstracts\ADatabase[]
     */
    private static $_Database   = [];

    /**
     * @var \DatabaseObject\DatabaseObjectDefinition|null
     */
    private $_definition        = NULL;

    /**
     * @var null|string
     */
    private $_database          = NULL;

    /**
     * @var array
     */
    private $_values            = [];

    /**
     * @var array
     */
    private $_updates           = [];

    /**
     * @var bool
     */
    private $_exists            = FALSE;

    /**
     * ADatabaseObject constructor.
     * @param null $id
     * @param null $index
     */
    public function __construct($id = NULL, $index = NULL) {
        $this->_definition = $this->createObjectDefinition();
        $this->retrieve($id, $index);
    }

    /**
     * To be implemented by the extender. Returns the database object definition
     * @return \DatabaseObject\DatabaseObjectDefinition
     */
    abstract public static function CreateObjectDefinition() : DatabaseObjectDefinition;

    /**
     * Used to get the objects database object definition
     * @return \DatabaseObject\DatabaseObjectDefinition
     */
    public function getObjectDefinition() : DatabaseObjectDefinition {
        return $this->_definition;
    }

    /**
     * Get the database values
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name) {
        if (isset($this->_values[$name])) {
            return $this->_values[$name];
        }
        return NULL;
    }

    /**
     * Set a database value
     * @param string $name
     * @param $value
     * @throws \Exception
     */
    public function __set(string $name, $value) {
        if ($this->_definition->hasColumn($name)) {
            $columnDefinition = $this->_definition->getColumn($name);
            $value = $columnDefinition->castValue($value);
            if ($this->_values[$name] != $value) {
                $this->_values[$name] = $value;
                $this->_updates[$name] = $value;
            }
        } else {
            throw new \Exception("Unknown column '$name'!");
        }
    }

    /**
     * Returns just the values for the database object
     * @return array
     */
    public function __debugInfo() : array {
        return ['values' => $this->_values];
    }

    /**
     * Store the databaseobject returns true on success
     * @return bool
     */
    public function store() : bool {
        if ($this->_exists) {
            echo 'Doing Existing' . PHP_EOL;
            $success = $this->_updateStore();
        } else {
            echo 'Doing non existing' . PHP_EOL;
            $success = $this->_insertStore();
        }
        $this->_exists = $success;
        return $success;
    }

    /**
     * Retrieve this database object
     * @param null $id
     * @param null $index
     * @return bool
     */
    public function retrieve($id = NULL, $index = NULL) : bool {
        /**
         * @var \DatabaseObject\DatabaseObjectColumn $column
         */
        foreach ($this->_definition as $column) {
            $this->_values[$column->getName()] = $column->getDefaultValue();
        }

        if ($id === NULL) {
            return TRUE;
        }

        if ($index === NULL) {
            $index = $this->_definition->getIdColumn()->getDatabaseName();
        }
        $db = $this->getDatabase();
        $pdo = $db->connect();
        $columns = $this->_definition->getDatabaseColumnNames();
        $columns = implode(',', $columns);
        $table = $this->_definition->getTable();

        $statement = $pdo->prepare("SELECT $columns FROM $table WHERE `$index` = :id");
        foreach ($this->_definition as $column) {
            $statement->bindColumn($column->getDatabaseName(), $this->_values[$column->getName()], \PDO::PARAM_STR);
        }
        $success = $statement->execute([':id' => $id]);
        if ($success) {
            $success = $statement->fetch(\PDO::FETCH_BOUND);
            foreach ($this->_values as $column => $value) {
                $colObj = $this->_definition->getColumn($column);
                $this->_values[$column] = $colObj->castValue($value);
            }
        }

        $db->disconnect();
        $this->_exists = $success;
        return $success;
    }

    /**
     * Delete the database object from the database. Returns TRUE on success FALSE on failure
     * @return bool
     */
    public function delete() : bool {
        $db = $this->getDatabase();
        $pdo = $db->connect();

        $idCol = $this->_definition->getIdColumn();
        $table = $this->_definition->getTable();

        $id = $idCol->getDatabaseName();
        $idValue = $this->{$idCol->getName()};
        $values = [':id' => $idValue];

        $statement = $pdo->prepare("DELETE FROM $table WHERE `$id` = :id");
        $success = $statement->execute($values);
        if ($success) {
            $this->_exists = FALSE;
        }
        $db->disconnect();
        return $success;
    }

    /**
     * Used for insertion store for non existing database objects
     * @return bool TRUE on success FALSE on failure
     */
    private function _insertStore() : bool {
        $db = $this->getDatabase();
        $pdo = $db->connect();
        $table = $this->_definition->getTable();
        $columns = [];
        foreach ($this->_values as $colName => $value) {
            $columnObj = $this->_definition->getColumn($colName);
            $columns[':' . $colName] = '`' . $columnObj->getDatabaseName() . '`';
            if (is_bool($value)) { //ensure that boolean values are correctly stored by casting them to an integer value
                $value = (int) $value;
            }
            $values[':' . $colName] = $value;
        }
        var_dump($columns);
        var_dump($values);
        $this->_updates = [];
        $columnStr = implode(', ', $columns);
        $valueStr = implode(', ', array_keys($columns));

        $statement = $pdo->prepare("REPLACE INTO $table($columnStr) VALUES($valueStr)");
        $success = $statement->execute($values);
        if ($success) {
            $this->_updateIdField($pdo);
        } else {
            var_dump($statement->errorInfo());
        }
        $db->disconnect();
        return $success;
    }

    /**
     * Used for update storing existing database objects
     * @return bool TRUE on success FALSE on failure
     */
    private function _updateStore() : bool {
        $db = $this->getDatabase();
        $pdo = $db->connect();
        $table = $this->_definition->getTable();
        $updates = [];
        $idCol = $this->_definition->getIdColumn();
        $id = $idCol->getDatabaseName();
        $idValue = $this->{$idCol->getName()};
        $values = [':id' => $idValue];
        foreach ($this->_updates as $colName => $value) {
            $columnObj = $this->_definition->getColumn($colName);
            $updates[] = '`' . $columnObj->getDatabaseName() . '`= :' . $colName;
            $values[':' . $colName] = $value;
        }
        $queryPart = implode(', ', $updates);
        $this->_updates = [];
        $statement = $pdo->prepare("UPDATE $table SET $queryPart WHERE `$id` = :id");
        $success = $statement->execute($values);
        if ($success) {
            $this->_updateIdField($pdo);
        }

        $db->disconnect();
        return $success;
    }

    /**
     * Used to update the id field after a store action
     * @param \PDO $pdo
     */
    private function _updateIdField(\PDO $pdo) {
        if (!$this->exists()) {
            $idName = $this->_definition->getIdColumn()->getName();
            $this->_values[$idName] = $pdo->lastInsertId();
        }
    }

    /**
     * Used to check whether the dbo exists
     * @return bool
     */
    public function exists() : bool {
        return $this->_exists;
    }

    /**
     * Set the database for this object
     * @param \DatabaseObject\Abstracts\ADatabase $database
     */
    public function setDatabase(ADatabase $database) {
        $this->_database = $database;
    }

    /**
     * Get the current database. If the current database is not set, it will set the database to the default database and return the default database
     * @return \DatabaseObject\Abstracts\ADatabase
     */
    public function getDatabase() : ADatabase {
        if ($this->_database === NULL) {
            $this->_database = static::GetDefaultDatabase();
        }
        return $this->_database;
    }

    /**
     * Set the default database for this object
     * @param \DatabaseObject\Abstracts\ADatabase $database
     */
    public static function SetDefaultDatabase(ADatabase $database) {
        self::$_Database[static::class] = $database;
    }

    /**
     * Returns the default database for the dbo
     * @return \DatabaseObject\Abstracts\ADatabase
     * @throws \Exception
     */
    public static function GetDefaultDatabase() : ADatabase {
        if (isset(self::$_Database[static::class])) {
            return self::$_Database[static::class];
        }
        if (isset(self::$_Database[self::class])) {
            return self::$_Database[self::class];
        }
        throw new \Exception('Default database is not set!');
    }

    /**
     * @param int $retrievalMode
     * @param array $whereClauses
     * @param int|NULL $limit
     * @param \DatabaseObject\Abstracts\ADatabase|NULL $database
     * @return array
     */
    public static function Find(array $whereClauses = [], int $limit = NULL, int $retrievalMode = self::RETRIEVAL_OBJECT, ADatabase $database = NULL) {
        if ($database === NULL) {
            $database = static::GetDefaultDatabase();
        }

        $objectDefinition = static::CreateObjectDefinition();
        $table = $objectDefinition->getTable();


        $queryParts = [];
        $values = [];

        foreach ($whereClauses as $key => $value) {
            if (is_array($value)) {
                list($column, $operator, $colVal) = $value;
                $queryParts[] = "`$column` $operator :$column";
                $values[':' . $column] = $colVal;
            } else {
                $queryParts[] = "`$key` = :$key";
                $values[':' . $key] = $value;
            }
        }
        $queryString = implode(' AND ', $queryParts);
        if ($limit !== NULL && $limit > 0) {
            $queryString .= ' LIMIT ' . $limit;
        }
        switch ($retrievalMode) {
            case self::RETRIEVAL_STATEMENT:
            case self::RETRIEVAL_OBJECT:
            default:
                $query = "SELECT * FROM $table WHERE $queryString";
                break;
            case self::RETRIEVAL_COUNT:
                $query = "SELECT COUNT(*) FROM $table WHERE $queryString";
                break;
        }
        $pdo = $database->connect();
        $statement = $pdo->prepare($query);

        $success = $statement->execute($values);

        if (!$success) {
            return FALSE;
        }

        switch ($retrievalMode) {
            case self::RETRIEVAL_STATEMENT:
                return $statement;
            case self::RETRIEVAL_COUNT:
                list($count) = $statement->fetch(\PDO::FETCH_NUM);
                return $count;
            default:
            case self::RETRIEVAL_OBJECT:
                $ret = [];
                $res = $statement->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($res as $resAssoc) {
                    $obj = new static;
                    /**
                     * @var \DatabaseObject\DatabaseObjectColumn $column
                     */
                    foreach ($objectDefinition as $column) {
                        $internalField = $column->getDatabaseName();
                        if (isset($resAssoc[$internalField])) {
                            $obj->{$column->getName()} = $resAssoc[$internalField];
                        }
                    }
                    $obj->_exists = TRUE;
                    $ret[] = $obj;
                }
                return $ret;
        }
    }

}
