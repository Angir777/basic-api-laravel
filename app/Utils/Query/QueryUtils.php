<?php

namespace App\Utils\Query;

class QueryUtils
{
    // Funkcja zamieniająca nam property na snake_case dla bazy danych pgSQL
    public static function snakeCase($value): string
    {
        $value = preg_replace('/\s+/u', '', ucwords($value));

        return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $value));
    }
}
