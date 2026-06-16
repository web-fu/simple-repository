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
    /**
     * @return iterable<string, array{query: string, expected: string}>
     */
    public function queryProvider(): iterable
    {
        yield 'select with where' => [
            'query'    => 'SELECT * FROM user WHERE id = :id',
            'expected' => "SELECT * FROM user WHERE id = :id_0",
        ];
    }

    public function testMultipleQuery(): void
    {
        $this->expectNotToPerformAssertions();

        $pdo = new \PDO('mysql:host=127.0.0.1;dbname='.$_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);

        $databaseWrapper = new DatabaseWrapper($pdo);
        $databaseWrapper->query('SELECT * FROM user WHERE id = :id', ['id' => 1]);
        $databaseWrapper->query('SELECT * FROM user WHERE username = :username', ['username' => 'foo']);

    }
}