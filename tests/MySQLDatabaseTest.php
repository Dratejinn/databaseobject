<?php

declare(strict_types=1);

namespace DatabaseObjectTests;

use PHPUnit\Framework\TestCase;

use DatabaseObject;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @covers MySQLDatabase
 */
final class MySQLDatabaseTest extends TestCase {

    private function _getConnectionDetails() {
        return new DatabaseObject\MySQLConnectionDetails;
    }

    public function testConnectionDetailsCanBeConstructed() {
        $this->assertInstanceOf(
            DatabaseObject\MySQLConnectionDetails::class,
            $this->_getConnectionDetails()
        );
    }

    public function testCredentialsCanBeConstructed() {
        global $username, $password;
        $this->assertInstanceOf(
            DatabaseObject\UserCredentials::class,
            new DatabaseObject\UserCredentials($username, $password)
        );
    }
}
