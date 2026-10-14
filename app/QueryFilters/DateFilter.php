<?php

namespace App\QueryFilters;

use App\Utils\Query\QueryUtils;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * Poprawia filtrowanie po datach w bazie pgSQL
 */
class DateFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $snakeCaseProperty = QueryUtils::snakeCase($property);

        if (is_array($value)) {
            $stringValue = implode(',', $value);
        } else {
            $stringValue = (string) $value;
        }

        $query->whereRaw("$snakeCaseProperty::text ilike ?", ["%{$stringValue}%"]);
    }
}
