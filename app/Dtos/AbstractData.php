<?php

namespace App\Dtos;

use ReflectionClass;
use ReflectionProperty;

abstract class AbstractData
{
    /**
     * Create an instance from an array.
     */
    public static function from(array $data): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return new static;
        }

        $parameters = $constructor->getParameters();
        $args = [];

        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $value = $data[$paramName] ?? null;

            if ($value !== null && $parameter->hasType()) {
                $type = $parameter->getType();
                if ($type && ! $type->isBuiltin()) {
                    $typeName = $type->getName();
                    if (is_subclass_of($typeName, self::class)) {
                        // If value is already an instance of the expected type, use it as-is
                        if ($value instanceof $typeName) {
                            // Value is already the correct type, no conversion needed
                        } elseif (is_array($value)) {
                            $value = $typeName::from($value);
                        }
                        // If it's neither an array nor the expected type, leave it as-is
                    }
                }
            }

            $args[] = $value;
        }

        return new static(...$args);
    }

    /**
     * Convert the object to an array.
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $data = [];

        foreach ($properties as $property) {
            $value = $property->getValue($this);
            $data[$property->getName()] = $this->serializeValue($value);
        }

        return $data;
    }

    /**
     * Serialize a value recursively.
     */
    protected function serializeValue(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map([$this, 'serializeValue'], $value);
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        return $value;
    }
}
