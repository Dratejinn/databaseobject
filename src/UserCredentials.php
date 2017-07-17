<?php

declare(strict_types = 1);

namespace DatabaseObject;

class UserCredentials implements Interfaces\IUserCredentials {

    /**
     * @var string
     */
    private $_username = NULL;

    /**
     * @var null|string
     */
    private $_password = NULL;

    /**
     * UserCredentials constructor.
     * @param string $username
     * @param string|NULL $password
     */
    public function __construct(string $username, string $password = NULL) {
        $this->_username = $username;
        $this->_password = $password;
    }

    /**
     * @inheritdoc
     */
    public function getUsername() : string {
        return $this->_username;
    }

    /**
     * @inheritdoc
     */
    public function getPassword() : string {
        return $this->_password;
    }

}
