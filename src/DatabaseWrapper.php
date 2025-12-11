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

use Exception;
use Iterator;
use PDO;
use PDOStatement;
use WebFu\SimpleRepository\Exception\RepositoryException;

class DatabaseWrapper
{
    private PDO $connection;
    private PDOStatement $stmt;

    private string $formattedQuery;
    /**
     * @var array<string, mixed>
     */
    private array $formattedData = [];

    public function __construct(
        PDO $connection
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
        $stmt = $this->preparedQuery($query, $params);

        try {
            foreach ($this->formattedData as $key => $value) {
                $type = self::getPdoType($value);
                $stmt->bindValue($key, $value, $type);
            }

            $stmt->execute();
        } catch (Exception $e) {
            throw new RepositoryException($e->getMessage().' in the query:'.$this->formattedQuery.' data:'.print_r($this->formattedData, true), $e->getCode(), $e);
        }

        $this->stmt = $stmt;

        return $this;
    }

    public function getFormattedQuery(): string
    {
        return $this->formattedQuery;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->stmt->fetch(PDO::FETCH_COLUMN);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRow(): array
    {
        /** @var array<string, mixed>|false  */
        $result = $this->stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: [];
    }

    /**
     * @return array<int, mixed>
     */
    public function getColumn(): array
    {
        $result = $this->stmt->fetchAll(PDO::FETCH_COLUMN);

        return $result ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTable(): array
    {
        $result = $this->stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ?: [];
    }

    /**
     * @return Iterator<array<string, mixed>>
     */
    public function getTableIterator(): Iterator
    {
        while ($result = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
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
        $result = $this->stmt->fetchAll(PDO::FETCH_GROUP);

        return $result ?: [];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function getUniqueGroup(): array
    {
        $result = $this->stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

        return $result ?: [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getKeyPair(): array
    {
        $result = $this->stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return $result ?: [];
    }

    public function lastId(): int
    {
        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param string $query
     * @param array<string, mixed> $data
     * @throws RepositoryException
     * @return PDOStatement
     */
    protected function preparedQuery(string $query, array $data = []): PDOStatement
    {
        preg_match_all('/:\w+/', $query, $matches);
        $neededParams = $matches[0];

        $missingData = [];
        $subQuery = '';
        foreach ($neededParams as $param) {
            $pos = (int) strpos($query, $param);
            $subQuery .= substr($query, 0, $pos);
            $subQuery .= $param.'_'.substr_count($subQuery, $param);
            $query = substr($query, $pos + strlen($param));

            if (!$data[$param]) {
                $missingData[] = $param;
                continue;
            }

            $this->formattedData[$param.'_'.substr_count($subQuery, $param)] = $data[$param];
        }

        $this->formattedQuery = $subQuery.$query;

        // Check if there are missing data
        if ($missingData) {
            throw new RepositoryException('Missing Data to complete query:'.PHP_EOL.print_r($missingData, true).' SQL:'.$query, 500);
        }

        $stmt = $this->connection->prepare($this->formattedQuery);

        if (!$stmt) {
            $errorInfo = $this->connection->errorInfo();
            throw new RepositoryException('Prepare Error: '.$errorInfo[2].' in the query:'.$this->formattedQuery, $errorInfo[0]);
        }

        return $stmt;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws RepositoryException
     * @return array<string, mixed>
     */
    protected static function formatData(string $query, array $params = []): array
    {
        preg_match_all('/:\w+/', $query, $neededParams);
        $neededParams = $neededParams[0];

        $data = [];

        // Add : if needed
        /** @var string $key */
        foreach ($params as $key => $value) {
            if (':' !== $key[0]) {
                $params[':'.$key] = $value;
                unset($params[$key]);
            }
        }

        // Create a data binding
        foreach ($neededParams as $neededParam) {
            /** @var string $key */
            $key = preg_replace('/_\d+$/', '', $neededParam);
            if (array_key_exists($key, $params)) {
                $data[$neededParam] = $params[$key];
            }
        }

        if ($missingData = array_diff($neededParams, array_keys($data))) {
            throw new RepositoryException('Missing Data to complete query:'.PHP_EOL.print_r($missingData, true).' SQL:'.$query, 500);
        }

        return $data;
    }

    /**
     * @param mixed $value
     */
    protected static function getPdoType($value): int
    {
        switch (gettype($value)) {
            case 'boolean':
                return PDO::PARAM_BOOL;
            case 'integer':
                return PDO::PARAM_INT;
            case 'NULL':
                return PDO::PARAM_NULL;
            default:
                return PDO::PARAM_STR;
        }
    }
}
