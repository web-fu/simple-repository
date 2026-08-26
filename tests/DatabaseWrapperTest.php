<?php
declare(strict_types=1);

/**
 * This file is part of web-fu/simple-repository
 *
 * @copyright Web-Fu <info@web-fu.it>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFu\SimpleRepository\Tests;

use PHPUnit\Framework\TestCase;
use WebFu\SimpleRepository\DatabaseWrapper;

/**
 * @coversDefaultClass \WebFu\SimpleRepository\DatabaseWrapper
 */
class DatabaseWrapperTest extends TestCase
{
    public function testMultipleQuery(): void
    {
        $this->expectNotToPerformAssertions();

        $pdo = new \PDO('mysql:host=127.0.0.1;dbname='.$_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);

        $databaseWrapper = new DatabaseWrapper($pdo);
        $databaseWrapper->query('SELECT * FROM user WHERE id = :id', ['id' => 1]);
        $databaseWrapper->query('SELECT * FROM user WHERE username = :username', ['username' => 'foo']);
    }

    public function testFormatQuery(): void
    {
        $query    = 'SELECT * FROM user WHERE id = :id AND username = :username AND email = :email AND id = :id';
        $expected = 'SELECT * FROM user WHERE id = :id_0 AND username = :username_0 AND email = :email_0 AND id = :id_1';

        $this->assertSame($expected, DatabaseWrapper::formatQuery($query));
    }

    public function testFormatData(): void
    {
        $query  = 'SELECT * FROM user WHERE id = :id_0 AND username = :username_0 AND email = :email_0 AND id = :id_1';
        $params = [
            'id'       => 1,
            'username' => 'foo',
            'email'    => 'john.doe@foo.com',
        ];

        $expected = [
            ':id_0'       => 1,
            ':username_0' => 'foo',
            ':email_0'    => 'john.doe@foo.com',
            ':id_1'       => 1,
        ];

        $this->assertSame($expected, DatabaseWrapper::formatData($query, $params));
    }
}