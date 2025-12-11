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

use PDO;
use PHPUnit\Framework\TestCase;
use WebFu\SimpleRepository\DatabaseWrapper;

/**
 * @coversNothing
 */
class DatabaseWrapperTest extends TestCase
{
    /**
     * @dataProvider queryProvider
     */
    public function testGetFormattedQuery(string $query, string $expected): void
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname='.$_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);

        $databaseWrapper = new DatabaseWrapper($pdo);
        $databaseWrapper->query($query, ['id' => 1]);

        $this->assertEquals($expected, $databaseWrapper->getFormattedQuery());
    }

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
}