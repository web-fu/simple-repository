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

abstract class Model implements \JsonSerializable
{
    /**
     * @var array<string, Column>
     */
    private array $metadata = [];

    /**
     * @param array<string, int|float|string|\DateTime|\DateTimeImmutable|null> $data
     */
    public function __construct(array $data = [])
    {
        $this->init();

        foreach ($data as $key => $value) {
            $this->setOrIgnore($key, $value);
        }
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        $result = [];
        foreach ($this->metadata as $propertyName => $column) {
            $property = new \ReflectionProperty(get_class($this), $propertyName);
            $property->setAccessible(true);
            $value = $property->getValue($this);
            if (
                ($column->getType() === Column::DATETIME || $column->getType() === Column::DATETIME_IMMUTABLE)
                && $value instanceof \DateTimeInterface
            ) {
                // Serialize using the configured format, falling back to DATE_ATOM.
                $format = $column->getFormat();
                if ($format === null) {
                    $format = DATE_ATOM;
                }
                $value = $value->format($format);
            }
            if (self::isEnumObject($value)) {
                $value = self::normalizeEnumForSerialization($value);
            }
            $result[$column->getName()] = $value;
            $property->setAccessible(false);
        }
        return $result;
    }

    private function init(): void {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            /** @var string $docComment */
            $docComment = preg_replace('#^\s*/\*\*([^/]+)\*/\s*$#', '$1', $property->getDocComment() ?: '');
            $docComment = preg_replace('/\R/', PHP_EOL, $docComment);

            /** @phpstan-ignore-next-line */
            $sanitized = trim(preg_replace('/^\s*\*\s*(.+)/m', '$1', $docComment));

            $annotations = array_filter(explode(PHP_EOL, $sanitized));

            $columnsByAnnotation = array_filter($annotations, function($annotation) {
                return stripos($annotation, '@column') !== false;
            });

            // parse annotation strings like: @column(name="email", nullable=false, length=150)
            $parsedFromAnnotation = null;
            foreach ($columnsByAnnotation as $column) {
                if (!preg_match('/@column\s*(?:\((.*)\))?/i', $column, $m)) {
                    continue;
                }
                $parsed = [];
                if (!empty($m[1])) {
                    $inside = $m[1];
                    preg_match_all('/\s*(name|type|default|nullable|length|format)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^,)\s]+))\s*(?:,|$)/i', $inside, $pairs, PREG_SET_ORDER);
                    foreach ($pairs as $p) {
                        $key = $p[1];
                        /** @phpstan-ignore-next-line */
                        $value = $p[2] !== '' ? $p[2] : ($p[3] !== '' ? $p[3] : $p[4]);
                        $lower = strtolower($value);
                        if ($lower === 'false') {
                            $value = false;
                        } elseif ($lower === 'true') {
                            $value = true;
                        } elseif (is_numeric($value) && ctype_digit((string)$value)) {
                            $value = (int)$value;
                        }
                        $parsed[$key] = $value;
                    }
                }
                $parsedFromAnnotation = $parsed;
            }

            if ($parsedFromAnnotation !== null) {
                // convert parsed annotation parameters into a Column instance
                $this->metadata[$property->getName()] = new Column(
                    /** @phpstan-ignore-next-line */
                    $parsedFromAnnotation['name'] ?? $property->getName(),
                        /** @phpstan-ignore-next-line */
                    $parsedFromAnnotation['type']        ?? null,
                        $parsedFromAnnotation['default'] ?? null,
                        /** @phpstan-ignore-next-line */
                    $parsedFromAnnotation['nullable'] ?? false,
                        /** @phpstan-ignore-next-line */
                        $parsedFromAnnotation['length'] ?? null,
                        /** @phpstan-ignore-next-line */
                        $parsedFromAnnotation['format'] ?? null,
                );
                continue;
            }

            // if running on PHP 8+ handle attributes
            if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80000) {
                $attributes         = $property->getAttributes();
                $columnsByAttribute = array_filter($attributes, function($attribute) {
                    return $attribute->getName() === Column::class;
                });

                foreach ($columnsByAttribute as $attr) {
                    // if Column is an attribute class, newInstance() should return a Column object
                    $instance = $attr->newInstance();
                    if ($instance instanceof Column) {
                        $this->metadata[$property->getName()] = $instance;
                        continue 2;
                    }
                }
            }
        }
    }

    /**
     * @param string $key
     * @param int|float|string|\DateTime|\DateTimeImmutable|null $value
     */
    private function setOrIgnore(string $key, $value): void
    {
        $column = array_filter($this->metadata, function(string $propertyName) use ($key) {
            return $this->metadata[$propertyName]->getName() === $key;
        }, ARRAY_FILTER_USE_KEY);

        if (!$column) {
            return;
        }

        $propertyName     = array_key_first($column);
        $columnDefinition = $column[$propertyName];

        $property = new \ReflectionProperty(get_class($this), $propertyName);

        $castedValue = $this->castValueForProperty($columnDefinition, $property, $value);

        $property->setAccessible(true);
        $property->setValue($this, $castedValue);
        $property->setAccessible(false);
    }

    /**
     * @param int|float|string|\DateTime|\DateTimeImmutable|null $value
     * @return mixed
     */
    private function castValueForProperty(Column $column, \ReflectionProperty $property, $value)
    {
        if ($value === null) {
            return null;
        }

        if ($column->getType() !== null) {
            if ($column->getType() === Column::ENUM) {
                $propertyType = $property->getType();
                if ($propertyType instanceof \ReflectionNamedType && self::isEnumType($propertyType->getName())) {
                    return self::castEnumToType($propertyType->getName(), $value);
                }

                throw new CastingException('Enum cast requires an enum-typed property.');
            }

            return self::castValue($column->getType(), $value);
        }

        $propertyType = $property->getType();
        if ($propertyType instanceof \ReflectionUnionType) {
            foreach ($propertyType->getTypes() as $unionType) {
                if ($unionType->getName() !== 'null') {
                    $propertyType = $unionType;
                    break;
                }
            }
        }
        if (!$propertyType instanceof \ReflectionNamedType) {
            return $value;
        }

        $typeName = $propertyType->getName();

        if (self::isEnumType($typeName)) {
            return self::castEnumToType($typeName, $value);
        }

        switch ($typeName) {
            case 'int':
                if (!is_numeric($value)) {
                    throw new CastingException('AUTO cast failed: value cannot be cast to int.');
                }
                return (int)$value;
            case 'float':
                if (!is_numeric($value)) {
                    throw new CastingException('AUTO cast failed: value cannot be cast to float.');
                }
                return (float)$value;
            case 'bool':
                if (is_bool($value)) {
                    return $value;
                }
                if (is_int($value)) {
                    return (bool)$value;
                }
                if (is_string($value)) {
                    $casted = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($casted !== null) {
                        return $casted;
                    }
                }
                throw new CastingException('AUTO cast failed: value cannot be cast to bool.');
            case 'string':
                if (is_scalar($value)) {
                    return (string)$value;
                }
                throw new CastingException('AUTO cast failed: value cannot be cast to string.');
            case 'array':
                if (is_array($value)) {
                    return $value;
                }
                throw new CastingException('AUTO cast failed: value cannot be cast to array.');
            case 'DateTime':
                return self::castValue(Column::DATETIME, $value);
            case 'DateTimeImmutable':
                return self::castValue(Column::DATETIME_IMMUTABLE, $value);
            default:
                return $value;
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function normalizeEnumForSerialization($value)
    {
        $enumClass = get_class($value);
        if (self::isBackedEnumType($enumClass)) {
            return $value->value;
        }

        return $value->name;
    }

    /**
     * @param mixed $value
     */
    private static function isEnumObject($value): bool
    {
        if (PHP_VERSION_ID < 80100 || !is_object($value) || !function_exists('enum_exists')) {
            return false;
        }

        return enum_exists(get_class($value));
    }

    private static function isEnumType(string $typeName): bool
    {
        if (PHP_VERSION_ID < 80100 || !function_exists('enum_exists')) {
            return false;
        }

        return enum_exists($typeName);
    }

    private static function isBackedEnumType(string $enumClass): bool
    {
        return PHP_VERSION_ID >= 80100
            && interface_exists('BackedEnum')
            && is_subclass_of($enumClass, \BackedEnum::class);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function castEnumToType(string $enumClass, $value)
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        if (!self::isBackedEnumType($enumClass)) {
            throw new CastingException(sprintf('Enum "%s" is not backed. Only backed enums are supported for scalar values.', $enumClass));
        }

        if (!is_string($value) && !is_int($value)) {
            throw new CastingException(sprintf('Enum "%s" expects a string or int backed value.', $enumClass));
        }

        try {
            return call_user_func([$enumClass, 'from'], $value);
        } catch (\Throwable $exception) {
            throw new CastingException(sprintf('Invalid backed value for enum "%s".', $enumClass), 0, $exception);
        }
    }

    private static function castValue(?string $type, $value)
    {
        switch ($type) {
            case COLUMN::INTEGER:
                if (!is_int($value)) {
                    throw new CastingException('Integer value must be an integer.');
                }
                return (int)$value;
            case COLUMN::FLOAT:
                if(!is_float($value) && !is_int($value)) {
                    throw new CastingException('Float value must be a float or an integer.');
                }
                return (float)$value;
            case COLUMN::BOOLEAN:
                if (!is_bool($value) && !is_int($value)) {
                    throw new CastingException('Boolean value must be a boolean or an integer.');
                }
                return (bool)$value;
            case COLUMN::JSON:
                if (!is_string($value)) {
                    throw new CastingException('JSON value must be a string.');
                }
                $casted = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new CastingException('Invalid JSON string: '.json_last_error_msg());
                }
                return $casted;
            case COLUMN::DATETIME:
                if (!is_string($value) && !($value instanceof \DateTime)) {
                    throw new CastingException('DateTime value must be a string or a DateTime instance.');
                }
                return $value instanceof \DateTime ? $value : new \DateTime((string) $value);
            case COLUMN::DATETIME_IMMUTABLE:
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
