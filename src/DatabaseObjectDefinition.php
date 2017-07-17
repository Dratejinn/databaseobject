<?php

declare(strict_types = 1);

namespace DatabaseObject;

class DatabaseObjectDefinition implements \IteratorAggregate {

    protected $_definition = [];

    protected $_idColumn = NULL;

    protected $_table = NULL;

    public function __construct(string $table = NULL) {
        $this->_table = $table;
    }

    public function addColumn(DatabaseObjectColumn $column) : DatabaseObjectDefinition {
        $this->_definition[$column->getName()] = $column;
        return $this;
    }

    public function getColumn(string $name) : DatabaseObjectColumn {
        if (!isset($this->_definition[$name])) {
            throw new \Exception("No column found with name '$column'!");
        }
        return $this->_definition[$name];
    }

    public function getIterator() : \Traversable {
        return new \ArrayIterator($this->_definition);
    }

    public function setColumnAsIndex(DatabaseObjectColumn $column) : DatabaseObjectDefinition {
        if (!isset($this->_definition[$column->getName()])) {
            $this->_definition[$column->getName()] = $column;
        }
        $this->_idColumn = $column;
        return $this;
    }

    public function hasColumn(string $name) : bool {
        return isset($this->_definition[$name]);
    }

    public function getIdColumn() : DatabaseObjectColumn {
        if (!isset($this->_idColumn)) {
            throw new \Exception('Id column is not set!');
        }
        return $this->_idColumn;
    }

    public function getInternalColumnNames() : array {
        $columnNames = [];
        foreach ($this->_definition as $column) {
            $columnNames[] = $column->getInternalName();
        }
        return $columnNames;
    }

    public function setTable(string $table) {
        $this->_table = $table;
    }

    public function getTable() : string {
        if (!isset($this->_table)) {
            throw new \Exception('No table provided!');
        }
        return $this->_table;
    }
}
