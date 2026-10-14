<?php

namespace App\QueryFilters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class UserPermissionFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $permissions = is_array($value)
            ? $value
            : explode(',', $value);

        $query->where(function (Builder $query) use ($permissions) {
            $query->whereHas('permissions', function (Builder $query) use ($permissions) {
                $query->whereIn('name', $permissions);
            });

            $query->orWhereHas('roles.permissions', function (Builder $query) use ($permissions) {
                $query->whereIn('name', $permissions);
            });
        });
    }
}
