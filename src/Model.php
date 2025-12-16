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
use ReflectionClass;
use ReflectionProperty;

abstract class Model
{
    /**
     * @var array<string, Column>
     */
    private array $metadata = [];

    /**
     * @param array<string, int|float|string|null> $data
     */
    public function __construct(array $data = [])
    {
        $this->init();

        foreach ($data as $key => $value) {
            $this->setOrIgnore($key, $value);
        }
    }

    private function init(): void {
        $reflection = new ReflectionClass($this);
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
                    preg_match_all('/\s*(name|type|default|nullable|length)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^,)\s]+))\s*(?:,|$)/i', $inside, $pairs, PREG_SET_ORDER);
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
                    $parsedFromAnnotation['type']        ?? Column::STRING,
                        $parsedFromAnnotation['default'] ?? null,
                        /** @phpstan-ignore-next-line */
                    $parsedFromAnnotation['nullable'] ?? false,
                        /** @phpstan-ignore-next-line */
                        $parsedFromAnnotation['length'] ?? null,
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
     * @param int|float|string|null $value
     */
    private function setOrIgnore(string $key, $value): void
    {
        $column = array_filter($this->metadata, function(string $propertyName) use ($key) {
            return $this->metadata[$propertyName]->getName() === $key;
        }, ARRAY_FILTER_USE_KEY);

        if (!$column) {
            return;
        }

        $propertyName = array_key_first($column);

        $property = new ReflectionProperty(get_class($this), $propertyName);
        $property->setAccessible(true);
        $property->setValue($this, $value);
        $property->setAccessible(false);
    }
}