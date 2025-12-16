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
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public const INTEGER  = 'integer';
    public const STRING   = 'string';
    public const FLOAT    = 'float';
    public const BOOLEAN  = 'boolean';
    public const JSON     = 'json';
    public const DATETIME = 'datetime';

    public function __construct(
        private null|string $name = null,
        private string $type = self::STRING,
        private $default = null,
        private bool $nullable = false,
        private null|int $length = null,
    ) {
    }

    public function getName(): null|string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }
    public function isNullable(): bool
    {
        return $this->nullable;
    }
    public function getLength(): null|int
    {
        return $this->length;
    }
}