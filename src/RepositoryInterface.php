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

interface RepositoryInterface
{
    /**
     * @param int|string $id
     *
     * @return object|array<string, mixed>|null
     */
    public function get($id);

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<int, object|array<string, mixed>>
     */
    public function search(array $criteria = []): array;

    /**
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria = []): int;

    /**
     * @param object|array<string, mixed> $entity
     *
     */
    public function save($entity): void;

    /**
     * @param object|array<string, mixed> $entity
     *
     */
    public function delete($entity): void;
}
