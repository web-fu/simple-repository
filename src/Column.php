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
use DateTime;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public const INTEGER  = 'integer';
    public const STRING   = 'string';
    public const FLOAT    = 'float';
    public const BOOLEAN  = 'boolean';
    public const JSON     = 'json';
    public const DATETIME = 'datetime';

    private ?string $name;
    private string $type;
    /**
     * @var int|float|string|bool|mixed[]|null $default
     */
    private $default;
    private bool $nullable;
    private ?int $length;

    /**
     * @param null|string $name
     * @param string $type
     * @param int|float|string|bool|mixed[]|null $default
     * @param bool $nullable
     * @param null|int $length
     */
    public function __construct(
        ?string $name = null,
        string $type = self::STRING,
        $default = null,
        bool $nullable = false,
        ?int $length = null
    ) {
        $this->name     = $name;
        $this->type     = $type;
        $this->default  = $default;
        $this->nullable = $nullable;
        $this->length   = $length;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): string
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

    public static function castValue(string $type, $value)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case self::INTEGER:
                return (int)$value;
            case self::FLOAT:
                return (float)$value;
            case self::BOOLEAN:
                return (bool)$value;
            case self::JSON:
                return is_string($value) ? json_decode($value, true) : $value;
            case self::DATETIME:
                return $value instanceof DateTime ? $value : new DateTime($value);
            default:
                return (string)$value;
        }
    }
}