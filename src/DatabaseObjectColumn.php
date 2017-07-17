<?php

declare(strict_types = 1);

namespace DatabaseObject;

class DatabaseObjectColumn {

    const TYPE_STRING   = 'string';
    const TYPE_FLOAT    = 'float';
    const TYPE_BOOL     = 'bool';
    const TYPE_INT      = 'int';

    private $_name          = NULL;
    private $_interalName   = NULL;
    private $_type          = NULL;
    private $_defaultValue  = NULL;

    public function __construct(string $name, string $type, $defaultValue = NULL, string $internalName = NULL) {
        $this->_name = $name;
        $this->_interalName = $internalName ?: $name;
        $this->_type = $type;
        $this->_defaultValue = $this->castValue($defaultValue);
    }

    public function getName() : string {
        return $this->_name;
    }

    public function getInternalName() : string {
        return $this->_interalName;
    }

    public function getType() : string {
        return $this->_type;
    }

    public function getDefaultValue() {
        return $this->_defaultValue;
    }

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

    private function _validateType(string $type) : bool {
        switch ($type) {
            case self::TYPE_STRING:
            case self::TYPE_FLOAT:
            case self::TYPE_BOOL:
            case self::TYPE_INT:
                return TRUE;
            default:
                return FALSE;
        }
    }

    public static function Create(string $name, string $type, $defaultValue = NULL, string $internalName = NULL) : DatabaseObjectColumn {
        return new static($name, $type, $defaultValue, $internalName);
    }
}
