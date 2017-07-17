<?php

declare(strict_types = 1);

namespace DatabaseObject\Traits;

trait TConnectionDetails {

    protected $_options = [];

    /**
     * Returns the connection details as a dsn part for \PDO
     * @return string
     */
    public function getConnectionDetailsAsDsnPart() : string {

        $connectionDetails = '';
        foreach ($this->_options as $key => $value) {
            $connectionDetails .= $key . '=' . $value . ';';
        }
        return $connectionDetails;
    }

}
