<?php

declare(strict_types = 1);

namespace DatabaseObject\Traits;

trait TConnectionDetails {

    protected $_options = [];

    public function getConnectionDetailsAsDsnPart() : string {

        $connectionDetails = '';
        foreach ($this->_options as $key => $value) {
            $connectionDetails .= $key . '=' . $value . ';';
        }
        return $connectionDetails;
    }

}
