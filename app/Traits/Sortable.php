<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Provides reusable backend column sorting with an allowlist of safe columns.
 *
 * Usage:
 *   use Sortable;
 *   $sort = $this->sortParams($request, ['name' => 'name', 'email' => 'email'], 'name');
 *   $this->applySorting($query, $sort);
 */
trait Sortable
{
    /** @return array{sort:string,direction:string} */
    protected function sortParams(Request $request, array $sortableColumns, string $defaultSort = 'name', string $defaultDir = 'asc'): array
    {
        $sort = $request->get('sort', $defaultSort);
        $direction = $request->get('direction', $defaultDir) === 'desc' ? 'desc' : 'asc';

        if (! array_key_exists($sort, $sortableColumns)) {
            $sort = $defaultSort;
        }

        return ['sort' => $sort, 'direction' => $direction];
    }

    protected function applySorting(Builder $query, array $sort, array $sortableColumns): void
    {
        $column = $sortableColumns[$sort['sort']] ?? $sortableColumns[array_key_first($sortableColumns)] ?? null;

        if ($column) {
            $query->orderBy($column, $sort['direction']);
        }
    }
}
