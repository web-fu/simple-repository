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

namespace WebFu\SimpleRepository;

use WebFu\SimpleRepository\Exception\RepositoryException;

class DatabaseWrapper
{
    private \PDO $connection;
    private \PDOStatement $stmt;

    public function __construct(
        \PDO $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws RepositoryException
     */
    public function query(string $query, array $params = []): self
    {
        $queryBuilder = new QueryBuilder($query, $params);

        [$formattedQuery, $formattedData] = $queryBuilder->getQueryAndData();

        $this->prepareQuery($formattedQuery)
            ->bindData($formattedData)
            ->execute();

        return $this;
    }

    public function prepareQuery(string $query): self
    {
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            $errorInfo = $this->connection->errorInfo();
            throw new RepositoryException('Prepare Error: '.$errorInfo[2].' in the query:'.$query);
        }

        $this->stmt = $stmt;

        return $this;
    }

    /**
     * @param array<non-falsy-string, mixed> $data
     */
    public function bindData(array $data): self
    {
        foreach ($data as $key => $value) {
            $type = self::getPdoType($value);
            try {
                $this->stmt->bindValue($key, $value, $type);
            } catch (\Throwable $e) {
                throw new RepositoryException('Bind Error on '.$key.': '.$e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this;
    }

    public function execute(): self {
        try {
            $this->stmt->execute();
        } catch (\Throwable $e) {
            throw new RepositoryException('Execute Error:'.$e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    public function beginTransaction(): self
    {
        $this->connection->beginTransaction();

        return $this;
    }

    public function commit(): self
    {
        $this->connection->commit();

        return $this;
    }

    public function rollback(): self
    {
        $this->connection->rollback();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->stmt->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRow(): array
    {
        /** @var array<string, mixed>|false  */
        $result = $this->stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: [];
    }

    /**
     * @return array<int, mixed>
     */
    public function getColumn(): array
    {
        $result = $this->stmt->fetchAll(\PDO::FETCH_COLUMN);

        return $result ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTable(): array
    {
        $result = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result ?: [];
    }

    /**
     * @return \Iterator<array<string, mixed>>
     */
    public function getTableIterator(): \Iterator
    {
        while ($result = $this->stmt->fetch(\PDO::FETCH_ASSOC)) {
            /** @var array<string, mixed> $result */
            yield $result;
        }
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function getGroup(): array
    {
        /** @var array<string, array<mixed>> $result */
        $result = $this->stmt->fetchAll(\PDO::FETCH_GROUP);

        return $result ?: [];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function getUniqueGroup(): array
    {
        $result = $this->stmt->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE);

        return $result ?: [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getKeyPair(): array
    {
        $result = $this->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        return $result ?: [];
    }

    public function lastId(): int
    {
        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param mixed $value
     */
    protected static function getPdoType($value): int
    {
        switch (gettype($value)) {
            case 'boolean':
                return \PDO::PARAM_BOOL;
            case 'integer':
                return \PDO::PARAM_INT;
            case 'NULL':
                return \PDO::PARAM_NULL;
            default:
                return \PDO::PARAM_STR;
        }
    }
}
