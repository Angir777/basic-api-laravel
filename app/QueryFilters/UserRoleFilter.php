<?php

namespace App\QueryFilters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class UserRoleFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $roles = is_array($value) ? $value : explode(',', $value);

        $query->whereHas('roles', function (Builder $query) use ($roles) {
            $query->whereIn('name', $roles);
        });
    }
}
