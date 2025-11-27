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
        $pdo = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->getMock();

        $databaseWrapper = new DatabaseWrapper($pdo);
        $databaseWrapper->query($query, ['id' => 1]);

        $this->assertEquals($expected, $databaseWrapper->getFormattedQuery());
    }

    public function queryProvider(): iterable
    {
        yield 'select with where' => [
            'query'    => 'SELECT * FROM user WHERE id = :id',
            'expected' => "SELECT * FROM user WHERE id = :id_0",
        ];
    }
}