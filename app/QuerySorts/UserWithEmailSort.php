<?php

namespace App\QuerySorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class UserWithEmailSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $direction = $descending ? 'desc' : 'asc';

        $query
            ->orderByRaw("LOWER(first_name) {$direction}")
            ->orderByRaw("LOWER(last_name) {$direction}");
    }
}
