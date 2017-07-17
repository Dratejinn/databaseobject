<?php

declare(strict_types = 1);

namespace DatabaseObject;

class DatabaseObjectColumn {

    const TYPE_STRING   = 'string';
    const TYPE_FLOAT    = 'float';
    const TYPE_BOOL     = 'bool';
    const TYPE_INT      = 'int';

    private $_name          = NULL;
    private $_databaseName  = NULL;
    private $_type          = NULL;
    private $_defaultValue  = NULL;

    /**
     * DatabaseObjectColumn constructor.
     * @param string $name
     * @param string $type
     * @param null $defaultValue
     * @param string|NULL $databaseName
     */
    public function __construct(string $name, string $type, $defaultValue = NULL, string $databaseName = NULL) {
        $this->_name = $name;
        $this->_databaseName = $databaseName ?: $name;
        $this->_type = $type;
        $this->_defaultValue = $this->castValue($defaultValue);
    }

    /**
     * Get the PHP name for the column
     * @return string
     */
    public function getName() : string {
        return $this->_name;
    }

    /**
     * Get the database name for the column
     * @return string
     */
    public function getDatabaseName() : string {
        return $this->_databaseName;
    }

    /**
     * Get the column type
     * @return string
     */
    public function getType() : string {
        return $this->_type;
    }

    /**
     * Get the optional default value for the column
     * @return bool|float|int|null
     */
    public function getDefaultValue() {
        return $this->_defaultValue;
    }

    /**
     * Cast a value to its correct type
     * @param $value
     * @return bool|float|int|null|string
     */
    public function castValue($value) {
        switch ($this->_type) {
            case self::TYPE_STRING:
                return (string) $value;
            case self::TYPE_FLOAT:
                return (float) $value;
            case self::TYPE_BOOL:
                return (bool) $value;
            case self::TYPE_INT:
                return (int) $value;
            default:
                return NULL;
        }
    }

    /**
     * Get the Database type
     * @return int
     */
    public function getPDOType() : int {
        switch ($this->_type) {
            case self::TYPE_BOOL:
                return \PDO::PARAM_BOOL;
            case self::TYPE_INT:
            case self::TYPE_FLOAT:
                return \PDO::PARAM_INT;
            case self::TYPE_STRING:
            default:
                return \PDO::PARAM_STR;
        }
    }

    /**
     * Creates a new column with provided arguments
     * @param string $name
     * @param string $type
     * @param null $defaultValue
     * @param string|NULL $databaseName
     * @return \DatabaseObject\DatabaseObjectColumn
     */
    public static function Create(string $name, string $type, $defaultValue = NULL, string $databaseName = NULL) : DatabaseObjectColumn {
        return new static($name, $type, $defaultValue, $databaseName);
    }
}
