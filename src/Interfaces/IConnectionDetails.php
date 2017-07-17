<?php

declare(strict_types = 1);

namespace DatabaseObject\Interfaces;

interface IConnectionDetails {

    /**
     * Used to get the connection details as a dsn part for \PDO to use
     * @return string
     */
    public function getConnectionDetailsAsDsnPart() : string;

}
