<?php

namespace DatabaseObject\Abstracts;

use DatabaseObject\DatabaseObjectDefinition;

abstract class ADatabaseObject {

    const RETRIEVAL_OBJECT      = 0x01;
    const RETRIEVAL_STATEMENT   = 0x03;
    const RETRIEVAL_COUNT       = 0x05;

    private static $_Database   = [];

    private $_definition        = NULL;
    private $_database          = NULL;
    private $_values            = [];
    private $_updates           = [];
    private $_exists            = FALSE;

    public function __construct($id = NULL, $index = NULL) {
        $this->_definition = $this->createObjectDefinition();
        $this->retrieve($id, $index);
    }

    abstract public static function CreateObjectDefinition() : DatabaseObjectDefinition;

    public function getObjectDefinition() : DatabaseObjectDefinition {
        return $this->_definition;
    }

    public function __get(string $name) {
        if (isset($this->_values[$name])) {
            return $this->_values[$name];
        }
        return NULL;
    }

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

    public function __debugInfo() : array {
        return ['values' => $this->_values];
    }

    public function store() : bool {
        if ($this->_exists) {
            echo 'Doing Existing' . PHP_EOL;
            $success = $this->_updateStore();
        } else {
            echo 'Doing non existing' . PHP_EOL;
            $success = $this->_insertStore();
        }
        $this->_exists = $success;
        var_dump($success);
        return $success;
    }

    public function retrieve($id = NULL, $index = NULL) : bool {
        foreach ($this->_definition as $column) {
            $this->_values[$column->getName()] = $column->getDefaultValue();
        }

        if ($id === NULL) {
            return TRUE;
        }

        if ($index === NULL) {
            $index = $this->_definition->getIdColumn()->getInternalName();
        }
        $db = $this->getDatabase();
        $pdo = $db->connect();
        $columns = $this->_definition->getInternalColumnNames();
        $columns = implode(',', $columns);
        $table = $this->_definition->getTable();

        $statement = $pdo->prepare("SELECT $columns FROM $table WHERE `$index` = :id");
        foreach ($this->_definition as $column) {
            $statement->bindColumn($column->getInternalName(), $this->_values[$column->getName()], \PDO::PARAM_STR);
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

    public function delete() : bool {
        $db = $this->getDatabase();
        $pdo = $db->connect();

        $idCol = $this->_definition->getIdColumn();
        $table = $this->_definition->getTable();

        $id = $idCol->getInternalName();
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

    private function _insertStore() : bool {
        $db = $this->getDatabase();
        $pdo = $db->connect();
        $table = $this->_definition->getTable();
        $columns = [];
        foreach ($this->_updates as $colName => $value) {
            $columnObj = $this->_definition->getColumn($colName);
            $columns[':' . $colName] = '`' . $columnObj->getInternalName() . '`';
            $values[':' . $colName] = $value;
        }
        $this->_updates = [];
        $columnStr = implode(', ', $columns);
        $valueStr = implode(', ', array_keys($columns));

        $statement = $pdo->prepare("REPLACE INTO $table($columnStr) VALUES($valueStr)");
        $success = $statement->execute($values);
        if ($success) {
            $this->_updateIdField($pdo);
        }
        $db->disconnect();
        return $success;
    }

    private function _updateStore() : bool {
        $db = $this->getDatabase();
        $pdo = $db->connect();
        $table = $this->_definition->getTable();
        $updates = [];
        $idCol = $this->_definition->getIdColumn();
        $id = $idCol->getInternalName();
        $idValue = $this->{$idCol->getName()};
        $values = [':id' => $idValue];
        foreach ($this->_updates as $colName => $value) {
            $columnObj = $this->_definition->getColumn($colName);
            $updates[] = '`' . $columnObj->getInternalName() . '`= :' . $colName;
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

    private function _updateIdField(\PDO $pdo) {
        if (!$this->exists()) {
            $idName = $this->_definition->getIdColumn()->getName();
            $this->_values[$idName] = $pdo->lastInsertId();

        }
    }

    public function exists() : bool {
        return $this->_exists;
    }

    public function setDatabase(ADatabase $database) {
        $this->_database = $database;
    }

    public function getDatabase() : ADatabase {
        if ($this->_database === NULL) {
            $this->_database = static::GetDefaultDatabase();
        }
        return $this->_database;
    }

    public static function SetDefaultDatabase(ADatabase $database) {
        self::$_Database[static::class] = $database;
    }

    public static function GetDefaultDatabase() : ADatabase {
        if (!isset(self::$_Database[static::class])) {
            throw new \Exception('Default database is not set!');
        }
        return self::$_Database[static::class];
    }

    public static function Find(int $retrievalMode = self::RETRIEVAL_OBJECT, array $whereClauses = [], int $limit = NULL, ADatabase $database = NULL) {
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
                $values[':' . $column] = $value;
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
                $query = 'SELECT * FROM $table WHERE $queryString';
                break;
            case self::RETRIEVAL_COUNT:
                $query = 'SELECT COUNT(*) FROM $table WHERE $queryString';
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
            case self::RETRIEVAL_OBJECT:
                $res = [];
                $res = $statement->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($res as $resAssoc) {
                    $obj = new static;
                    foreach ($objectDefinition as $column) {
                        $internalField = $column->getInternalName();
                        if (isset($resAssoc[$internalField])) {
                            $obj->{$column->getName()} = $resAssoc[$internalField];
                        }
                    }
                    $obj->_exists = TRUE;
                    $res[] = $obj;
                }
                return $res;
        }
    }

}
