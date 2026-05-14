<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms\Components\Select;
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

class CategoryResource extends Resource
{
    use Translatable;

    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Taxonomies';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Recipe Categories';

    protected static ?string $modelLabel = 'Recipe Category';

    protected static ?string $pluralModelLabel = 'Recipe Categories';

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
                Select::make('parent_id')
                    ->label('Parent category')
                    ->relationship('parent', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->name)
                    ->searchable()
                    ->preload()
                    ->nullable(),
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
                TextColumn::make('parent.name')->label('Parent')->placeholder('--'),
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
            'index' => Pages\ManageCategories::route('/'),
        ];
    }
}
