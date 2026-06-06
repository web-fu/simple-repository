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
use WebFu\SimpleRepository\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Column
{
    public const AUTO               = 'auto';
    public const INTEGER            = 'integer';
    public const STRING             = 'string';
    public const FLOAT              = 'float';
    public const BOOLEAN            = 'boolean';
    public const JSON               = 'json';
    public const DATETIME           = 'datetime';
    public const DATETIME_IMMUTABLE = 'datetime_immutable';

    private ?string $name;
    private ?string $type;
    /**
     * @var int|float|string|bool|mixed[]|null $default
     */
    private $default;
    private bool $nullable;
    private ?int $length;
    private ?string $format;

    /**
     * @param null|string $name
     * @param string $type
     * @param int|float|string|bool|mixed[]|null $default
     * @param bool $nullable
     * @param null|int $length
     * @param string $format
     */
    public function __construct(
        string $name,
        ?string $type = null,
        $default = null,
        bool $nullable = false,
        ?int $length = null,
        ?string $format = null
    ) {
        $this->name     = $name;
        $this->type     = $type;
        $this->default  = $default;
        $this->nullable = $nullable;
        $this->length   = $length;
        $this->format   = $format;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
    public function getLength(): ?int
    {
        return $this->length;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }
}