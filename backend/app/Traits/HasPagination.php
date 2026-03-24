<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasPagination
{
    protected function paginate(Builder $query, Request $request, int $defaultPerPage = 20, int $maxPerPage = 100): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = min(
            (int) $request->input('per_page', $defaultPerPage),
            $maxPerPage
        );

        return $query->paginate($perPage);
    }
}
