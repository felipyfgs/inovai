<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasPagination
{
    protected function paginate(Builder $query, Request $request, int $defaultPerPage = 20, int $maxPerPage = 100): LengthAwarePaginator
    {
        $perPage = min(
            (int) $request->input('per_page', $defaultPerPage),
            $maxPerPage
        );

        return $query->paginate($perPage);
    }
}
