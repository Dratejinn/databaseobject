<?php

declare(strict_types = 1);

namespace DatabaseObject;

class DatabaseObjectDefinition implements \IteratorAggregate {

    /**
     * @var \DatabaseObject\DatabaseObjectColumn[]
     */
    protected $_definition = [];

    protected $_idColumn = NULL;

    /**
     * the table name
     * @var null|string
     */
    protected $_table = NULL;

    /**
     * DatabaseObjectDefinition constructor.
     * @param string|NULL $table
     */
    public function __construct(string $table = NULL) {
        $this->_table = $table;
    }

    /**
     * Adds a column to the object definition
     * @param \DatabaseObject\DatabaseObjectColumn $column
     * @return \DatabaseObject\DatabaseObjectDefinition
     */
    public function addColumn(DatabaseObjectColumn $column) : DatabaseObjectDefinition {
        $this->_definition[$column->getName()] = $column;
        return $this;
    }

    /**
     * returns the column object associated with name
     * @param string $name
     * @return \DatabaseObject\DatabaseObjectColumn
     * @throws \Exception when there is no column with the provided name
     */
    public function getColumn(string $name) : DatabaseObjectColumn {
        if (!isset($this->_definition[$name])) {
            throw new \Exception("No column found with name '$name'!");
        }
        return $this->_definition[$name];
    }

    /**
     * Returns the traversable to iterate over all column objects
     * @return \Traversable
     */
    public function getIterator() : \Traversable {
        return new \ArrayIterator($this->_definition);
    }

    /**
     * Set column to be the index column
     * @param \DatabaseObject\DatabaseObjectColumn $column
     * @return \DatabaseObject\DatabaseObjectDefinition
     */
    public function setColumnAsIndex(DatabaseObjectColumn $column) : DatabaseObjectDefinition {
        if (!isset($this->_definition[$column->getName()])) {
            $this->_definition[$column->getName()] = $column;
        }
        $this->_idColumn = $column;
        return $this;
    }

    /**
     * Used to check if the definition has a column with name
     * @param string $name
     * @return bool
     */
    public function hasColumn(string $name) : bool {
        return isset($this->_definition[$name]);
    }

    /**
     * Returns the id column
     * @return \DatabaseObject\DatabaseObjectColumn
     * @throws \Exception
     */
    public function getIdColumn() : DatabaseObjectColumn {
        if (!isset($this->_idColumn)) {
            throw new \Exception('Id column is not set!');
        }
        return $this->_idColumn;
    }

    /**
     * Returns all database column names
     * @return string[]
     */
    public function getDatabaseColumnNames() : array {
        $columnNames = [];
        foreach ($this->_definition as $column) {
            $columnNames[] = $column->getDatabaseName();
        }
        return $columnNames;
    }

    /**
     * set the table name
     * @param string $table
     */
    public function setTable(string $table) {
        $this->_table = $table;
    }

    /**
     * Returns the table name if it is set
     * @return string
     * @throws \Exception
     */
    public function getTable() : string {
        if (!isset($this->_table)) {
            throw new \Exception('No table provided!');
        }
        return $this->_table;
    }
}
