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
    public const INTEGER  = 'integer';
    public const STRING   = 'string';
    public const FLOAT    = 'float';
    public const BOOLEAN  = 'boolean';
    public const JSON     = 'json';
    public const DATETIME = 'datetime';
    public const DATETIME_IMMUTABLE = 'datetime_immutable';

    private ?string $name;
    private string $type;
    /**
     * @var int|float|string|bool|mixed[]|null $default
     */
    private $default;
    private bool $nullable;
    private ?int $length;
    private string $format;

    /**
     * @param null|string $name
     * @param string $type
     * @param int|float|string|bool|mixed[]|null $default
     * @param bool $nullable
     * @param null|int $length
     * @param string $format Date format used when serialising a DATETIME column (default: DATE_ATOM)
     */
    public function __construct(
        ?string $name = null,
        string $type = self::STRING,
        $default = null,
        bool $nullable = false,
        ?int $length = null,
        string $format = DATE_ATOM
    ) {
        $this->name     = $name;
        $this->type     = $type;
        $this->default  = $default;
        $this->nullable = $nullable;
        $this->length   = $length;
        $this->format   = $format;
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

    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $type
     * @param mixed $value
     *
     * @return mixed
     */
    public static function castValue(string $type, $value)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case self::INTEGER:
                if (!is_int($value)) {
                    throw new CastingException('Integer value must be an integer.');
                }
                return (int)$value;
            case self::FLOAT:
                if(!is_float($value) && !is_int($value)) {
                    throw new CastingException('Float value must be a float or an integer.');
                }
                return (float)$value;
            case self::BOOLEAN:
                if (!is_bool($value) && !is_int($value)) {
                    throw new CastingException('Boolean value must be a boolean or an integer.');
                }
                return (bool)$value;
            case self::JSON:
                if (!is_string($value)) {
                    throw new CastingException('JSON value must be a string.');
                }
                $casted = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new CastingException('Invalid JSON string: '.json_last_error_msg());
                }
                return $casted;
            case self::DATETIME:
                if (!is_string($value) && !($value instanceof \DateTime)) {
                    throw new CastingException('DateTime value must be a string or a DateTime instance.');
                }
                return $value instanceof \DateTime ? $value : new \DateTime((string) $value);
            case self::DATETIME_IMMUTABLE:
                if (!is_string($value) && !($value instanceof \DateTimeImmutable)) {
                    throw new CastingException('DateTimeImmutable value must be a string or a DateTimeImmutable instance.');
                }
                return $value instanceof \DateTimeImmutable ? $value : new \DateTimeImmutable((string) $value);
            default:
                if (!is_string($value) && !is_numeric($value) && !is_bool($value)) {
                    throw new CastingException('String value must be a string, a numeric or a boolean.');
                }
                return (string)$value;
        }
    }
}