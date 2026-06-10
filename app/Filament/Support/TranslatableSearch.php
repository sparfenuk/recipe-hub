<?php

namespace App\Filament\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Reusable Filament table-column query closures for spatie/laravel-translatable
 * JSON columns: search across both locales, sort by the active locale. Shared by
 * the taxonomy resources and the Recipe/Ingredient resources so the JSON-column
 * query shape lives in exactly one place (CQ.7).
 */
final class TranslatableSearch
{
    /**
     * Case-insensitive search across the en + uk translations of a JSON column.
     *
     * @return Closure(Builder<Model>, string): Builder<Model>
     */
    public static function for(string $column): Closure
    {
        return fn (Builder $query, string $search): Builder => $query->where(function (Builder $q) use ($column, $search): void {
            $q->where("{$column}->en", 'like', "%{$search}%")
                ->orWhere("{$column}->uk", 'like', "%{$search}%");
        });
    }

    /**
     * Sort by the JSON column's value in the active locale.
     *
     * @return Closure(Builder<Model>, string): Builder<Model>
     */
    public static function sort(string $column): Closure
    {
        return fn (Builder $query, string $direction): Builder => $query->orderBy($column.'->'.app()->getLocale(), $direction);
    }
}
