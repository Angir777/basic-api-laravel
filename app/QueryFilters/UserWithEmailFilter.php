<?php

namespace App\QueryFilters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class UserWithEmailFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $query->where(function (Builder $query) use ($value) {
            $query
                ->where('first_name', 'ILIKE', "%{$value}%")
                ->orWhere('last_name', 'ILIKE', "%{$value}%")
                ->orWhere('email', 'ILIKE', "%{$value}%")
                ->orWhereRaw(
                    "CONCAT(first_name, ' ', last_name, ' (', email, ')') ILIKE ?",
                    ["%{$value}%"]
                );
        });
    }
}
