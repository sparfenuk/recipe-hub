<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllergenResource\Pages;
use App\Models\Allergen;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AllergenResource extends Resource
{
    use Translatable;

    protected static ?string $model = Allergen::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Taxonomies';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $q) use ($search): void {
                        $q->where('name->en', 'like', "%{$search}%")
                            ->orWhere('name->uk', 'like', "%{$search}%");
                    }))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('name->'.app()->getLocale(), $direction)),
                TextColumn::make('slug')->sortable(),
            ])
            ->defaultSort('slug')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAllergens::route('/'),
        ];
    }
}
