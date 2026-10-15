<?php

namespace App\Utils\Query;

class QueryUtils
{
    // A function that converts a property to snake_case for a pgSQL database.
    public static function snakeCase($value): string
    {
        $value = preg_replace('/\s+/u', '', ucwords($value));

        return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $value));
    }
}
