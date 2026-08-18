<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Str;

class Sort
{
    /**
     * Resolve the active sort column + direction from the request.
     * Falls back to the given defaults when no/invalid sort is provided.
     */
    public static function resolve(array $allowed, string $default = 'created_at', string $defaultDirection = 'desc'): array
    {
        $column = in_array(request()->query('sort'), $allowed, true)
            ? request()->query('sort')
            : $default;

        $direction = strtolower((string) request()->query('direction', $defaultDirection));

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultDirection;
        }

        return [$column, $direction];
    }

    /**
     * Apply orderBy to a query builder using the request sort params.
     */
    public static function apply(Builder $query, array $allowed, string $default = 'created_at', string $defaultDirection = 'desc'): Builder
    {
        [$column, $direction] = static::resolve($allowed, $default, $defaultDirection);

        return $query->orderBy($column, $direction);
    }

    /**
     * Whether the given column is currently the active sort.
     */
    public static function active(string $column): bool
    {
        return request()->query('sort') === $column;
    }

    /**
     * The direction that should be applied when the given column is clicked
     * (toggles asc <-> desc, defaulting to asc for a fresh column).
     */
    public static function nextDirection(string $column): string
    {
        if (! static::active($column)) {
            return 'asc';
        }

        return strtolower((string) request()->query('direction', 'asc')) === 'asc' ? 'desc' : 'asc';
    }

    /**
     * Whether the given column is currently sorted ascending.
     */
    public static function isAsc(string $column): bool
    {
        return static::active($column) && strtolower((string) request()->query('direction', 'asc')) === 'asc';
    }

    /**
     * Build the URL for sorting by the given column, preserving the current
     * query string (search/filters) while toggling the sort direction.
     */
    public static function url(string $column): string
    {
        $query = request()->except(['sort', 'direction', 'page']);
        $query['sort'] = $column;
        $query['direction'] = static::nextDirection($column);

        return url()->current().'?'.http_build_query($query);
    }

    /**
     * A display label for the active sort direction (used for screen readers).
     */
    public static function directionLabel(string $column): string
    {
        return static::active($column)
            ? Str::upper(static::isAsc($column) ? 'asc' : 'desc')
            : '';
    }
}