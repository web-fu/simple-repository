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
    public function get($id);

    public function search(array $criteria = []): array;

    public function count(array $criteria = []): int;

    public function save($entity);

    public function delete($entity);
}
