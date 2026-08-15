<?php

namespace App\Services\Maria\Support;

/**
 * A minimal, dependency-free validator for the JSON Schema subset actually used by
 * Maria's structured workflow schemas: object/array/string/integer/number/boolean
 * types, `properties`, `required`, `items`, `enum`, `minItems`, and `maxItems`.
 *
 * This is intentionally not a general-purpose JSON Schema implementation — it exists
 * to catch a malformed or incomplete Claude tool_use response before it is persisted
 * and shown to the user as a completed workflow result.
 */
class JsonSchemaValidator
{
    /** @return list<string> Validation error messages; empty means the data is valid. */
    public static function validate(mixed $data, array $schema, string $path = '$'): array
    {
        $errors = [];
        $type = $schema['type'] ?? null;

        if ($type && ! self::matchesType($data, $type)) {
            $errors[] = "{$path}: expected type '{$type}', got '".self::describeType($data)."'";

            return $errors;
        }

        if ($type === 'object' && is_array($data)) {
            foreach ((array) ($schema['required'] ?? []) as $field) {
                if (! array_key_exists($field, $data)) {
                    $errors[] = "{$path}: missing required field '{$field}'";
                }
            }

            foreach ((array) ($schema['properties'] ?? []) as $property => $propertySchema) {
                if (array_key_exists($property, $data)) {
                    $errors = [...$errors, ...self::validate($data[$property], $propertySchema, "{$path}.{$property}")];
                }
            }
        }

        if ($type === 'array' && is_array($data)) {
            if (isset($schema['minItems']) && count($data) < $schema['minItems']) {
                $errors[] = "{$path}: expected at least {$schema['minItems']} item(s), got ".count($data);
            }
            if (isset($schema['maxItems']) && count($data) > $schema['maxItems']) {
                $errors[] = "{$path}: expected at most {$schema['maxItems']} item(s), got ".count($data);
            }
            if (isset($schema['items'])) {
                foreach (array_values($data) as $index => $item) {
                    $errors = [...$errors, ...self::validate($item, $schema['items'], "{$path}[{$index}]")];
                }
            }
        }

        if (isset($schema['enum']) && ! in_array($data, $schema['enum'], true)) {
            $errors[] = "{$path}: expected one of [".implode(', ', $schema['enum'])."], got '".self::describeType($data)."'";
        }

        return $errors;
    }

    private static function matchesType(mixed $data, string $type): bool
    {
        return match ($type) {
            'object' => is_array($data) && ! array_is_list($data) || $data === [],
            'array' => is_array($data) && (array_is_list($data) || $data === []),
            'string' => is_string($data),
            'integer' => is_int($data),
            'number' => is_int($data) || is_float($data),
            'boolean' => is_bool($data),
            default => true,
        };
    }

    private static function describeType(mixed $data): string
    {
        return match (true) {
            is_array($data) => array_is_list($data) ? 'array' : 'object',
            is_null($data) => 'null',
            default => gettype($data),
        };
    }
}
