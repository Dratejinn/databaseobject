<?php

declare(strict_types = 1);

namespace DatabaseObject\Interfaces;

interface IUserCredentials {

    /**
     * Get the username required to log in to the database
     * @return string
     */
    public function getUsername() : string;

    /**
     * Get the password required to log the user in to the database
     * @return string
     */
    public function getPassword() : string;
}
