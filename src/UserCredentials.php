<?php

declare(strict_types = 1);

namespace DatabaseObject;

class UserCredentials implements Interfaces\IUserCredentials {

    private $_username = NULL;
    private $_password = NULL;

    public function __construct(string $username, string $password = NULL) {
        $this->_username = $username;
        $this->_password = $password;
    }

    public function getUsername() : string {
        return $this->_username;
    }

    public function getPassword() : string {
        return $this->_password;
    }

}
