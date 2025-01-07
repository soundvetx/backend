<?php

namespace App\Utils;

class Transformer
{
    public static function camelToSnakeCase(array|string $data): array|string
    {
        if (is_string($data)) {
            return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $data));
        }

        $result = [];

        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $result[$key] = self::camelToSnakeCase($value);
                continue;
            }

            if (is_array($value)) {
                $value = self::camelToSnakeCase($value);
            }

            $snakeCaseKey = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $key));
            $result[$snakeCaseKey] = $value;
        }

        return $result;
    }
}
