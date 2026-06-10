<?php

namespace App\Filament\Concerns;

use App\Filament\Support\TranslatableSearch;
use Filament\Forms\Components\Component as FormComponent;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Shared form + table scaffolding for the translatable name/slug taxonomy
 * resources (Category, Cuisine, Tag, Allergen, IngredientCategory), which were
 * five near-identical copies. A trait (not a base class) keeps each resource a
 * direct Filament Resource subclass, so panel discovery and the Translatable
 * concern behave exactly as before. Resources add genuinely unique fields,
 * columns and filters through the extra*() hooks (CQ.7).
 */
trait HasTaxonomyResource
{
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(100)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
            TextInput::make('slug')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),
            ...static::extraFormFields(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(query: TranslatableSearch::for('name'))
                    ->sortable(query: TranslatableSearch::sort('name')),
                TextColumn::make('slug')->sortable(),
                ...static::extraTableColumns(),
            ])
            ->defaultSort('slug')
            ->filters(static::extraTableFilters())
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Form fields beyond name + slug (e.g. a parent select, a type select).
     *
     * @return array<int, FormComponent>
     */
    protected static function extraFormFields(): array
    {
        return [];
    }

    /**
     * Table columns beyond name + slug.
     *
     * @return array<int, Column>
     */
    protected static function extraTableColumns(): array
    {
        return [];
    }

    /**
     * Table filters.
     *
     * @return array<int, BaseFilter>
     */
    protected static function extraTableFilters(): array
    {
        return [];
    }
}
