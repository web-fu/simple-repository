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

require_once __DIR__.'/../vendor/autoload.php';

/**
 * This file is part of web-fu/simple-repository
 *
 * @copyright Web-Fu <info@web-fu.it>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use WebFu\SimpleRepository\DatabaseWrapper;
use WebFu\SimpleRepository\RepositoryInterface;

$repositoryClass = new class extends DatabaseWrapper implements RepositoryInterface
{
    /**
     * @param int|string $id
     *
     * @return object|array<string, mixed>|null
     */
    public function get($id) {
        $sql = "SELECT * FROM some_table WHERE id = :id";
        $row = $this->query($sql, ['id' => $id])->getRow();

        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<int, object|array<string, mixed>>
     */
    public function search(array $criteria = []): array {
        $sql    = "SELECT * FROM some_table";
        $params = [];
        if (!empty($criteria)) {
            $conditions = [];
            foreach ($criteria as $key => $value) {
                $conditions[] = "$key = :$key";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        return $this->query($sql, $params)->getTable();
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria = []): int {
        $sql    = "SELECT COUNT(*) as count FROM some_table";
        $params = [];
        if (!empty($criteria)) {
            $conditions = [];
            foreach ($criteria as $key => $value) {
                $conditions[] = "$key = :$key";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        $count = $this->query($sql, $params)->getValue();

        return (int) $count;
    }

    /**
     * @param object|array<string, mixed> $entity
     *
     */
    public function save($entity): void {
        // Implementation for saving the entity to the database
    }

    /**
     * @param object|array<string, mixed> $entity
     *
     */
    public function delete($entity): void {
        // Implementation for deleting the entity from the database
    }
};

$pdo = new PDO('mysql:host=localhost;dbname=test', 'root', '');

$repository = new $repositoryClass($pdo);
$user       = $repository->get(1);
$list       = $repository->search(['name' => 'example']);
$number     = $repository->count(['status' => 'active']);