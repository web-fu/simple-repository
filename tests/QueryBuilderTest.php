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
use WebFu\SimpleRepository\QueryBuilder;

/**
 * @coversDefaultClass \WebFu\SimpleRepository\QueryBuilder
 */
class QueryBuilderTest extends TestCase
{
    /**
     * @covers ::getQueryAndData
     */
    public function testGetQueryAndData(): void
    {
        $sql = <<<'SQL'
                INSERT INTO account SET
                    id = :id, 
                    email  = :email, 
                    password = :password, 
                    status = :status,
                ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id), 
                    email  = :email, 
                    password = :password, 
                    status = :status,
                    updated_at = NOW()
            SQL;

        $queryBuilder = new QueryBuilder($sql, [
            'id'       => 1,
            'email'    => 'john.doe@foo.com',
            'password' => 'p@$$w0rd',
            'status'   => 1,
        ]);

        [$actualSql, $actualData] = $queryBuilder->getQueryAndData();

        $expectedSql = <<<'SQL'
                INSERT INTO account SET
                    id = :id_0, 
                    email  = :email_0, 
                    password = :password_0, 
                    status = :status_0,
                ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id), 
                    email  = :email_1, 
                    password = :password_1, 
                    status = :status_1,
                    updated_at = NOW()
            SQL;

        $this->assertEquals($expectedSql, $actualSql);

        $this->assertEquals([
            ':id_0'       => 1,
            ':email_0'    => 'john.doe@foo.com',
            ':password_0' => 'p@$$w0rd',
            ':status_0'   => 1,
            ':email_1'    => 'john.doe@foo.com',
            ':password_1' => 'p@$$w0rd',
            ':status_1'   => 1,
        ], $actualData);
    }
}
