<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersStartingWith implements Filter {
    public function __invoke(Builder $query, mixed $value, string $property)
    {
        return $query->where($property = 'word', 'like', "$value%");
    }
}