<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IngredientResource\Pages;
use App\Models\Allergen;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Tag;
use App\Models\Unit;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class IngredientResource extends Resource
{
    use Translatable;

    protected static ?string $model = Ingredient::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic info')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, $state, $livewire): void {
                                $activeLocale = $livewire->activeLocale ?? 'en';
                                if ($activeLocale !== 'en') {
                                    return;
                                }

                                $set('slug', Str::slug(is_string($state) ? $state : ''));
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(150)
                            ->unique(ignoreRecord: true),
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'slug')
                            ->getOptionLabelFromRecordUsing(fn (IngredientCategory $record): string => $record->name)
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('default_unit_id')
                            ->label('Default unit')
                            ->relationship('defaultUnit', 'code')
                            ->getOptionLabelFromRecordUsing(fn (Unit $record): string => $record->name)
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        TextInput::make('density_g_per_ml')
                            ->label('Density (g/ml)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.0001)
                            ->nullable(),
                        TextInput::make('piece_weight_g')
                            ->label('Piece weight (g)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->nullable(),
                        TextInput::make('source')
                            ->maxLength(100)
                            ->nullable()
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->default(true),
                        SpatieMediaLibraryFileUpload::make('photo')
                            ->collection('photo')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ]),

                Section::make('Nutrition per 100 g')
                    ->columns(3)
                    ->schema([
                        TextInput::make('kcal_per_100g')
                            ->label('Calories (kcal)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->nullable(),
                        TextInput::make('protein_g')
                            ->label('Protein (g)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->nullable(),
                        TextInput::make('fat_g')
                            ->label('Fat (g)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->nullable(),
                        TextInput::make('saturated_fat_g')
                            ->label('Saturated fat (g)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->nullable(),
                        TextInput::make('carbs_g')
                            ->label('Carbs (g)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->nullable(),
                        TextInput::make('sugar_g')
                            ->label('Sugar (g)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->nullable(),
                        TextInput::make('fiber_g')
                            ->label('Fiber (g)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->nullable(),
                        TextInput::make('sodium_mg')
                            ->label('Sodium (mg)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->nullable(),
                    ]),

                Section::make('Allergens & Tags')
                    ->columns(2)
                    ->schema([
                        Select::make('allergens')
                            ->relationship('allergens', 'slug')
                            ->getOptionLabelFromRecordUsing(fn (Allergen $record): string => $record->name)
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        Select::make('tags')
                            ->relationship('tags', 'slug')
                            ->getOptionLabelFromRecordUsing(fn (Tag $record): string => $record->name)
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),

                Section::make('Aliases')
                    ->schema([
                        Repeater::make('aliases')
                            ->relationship()
                            ->simple(
                                TextInput::make('alias')
                                    ->required()
                                    ->maxLength(255),
                            )
                            ->defaultItems(0)
                            ->addActionLabel('Add alias'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')
                    ->collection('photo')
                    ->conversion('thumb')
                    ->circular()
                    ->defaultImageUrl(fn () => '')
                    ->label(''),
                TextColumn::make('name')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $q) use ($search): void {
                        $q->where('name->en', 'like', "%{$search}%")
                            ->orWhere('name->uk', 'like', "%{$search}%");
                    }))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('name->'.app()->getLocale(), $direction)),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('--'),
                TextColumn::make('kcal_per_100g')
                    ->label('kcal/100g')
                    ->numeric(0)
                    ->sortable(),
                TextColumn::make('protein_g')
                    ->label('P')
                    ->numeric(1)
                    ->sortable(),
                TextColumn::make('fat_g')
                    ->label('F')
                    ->numeric(1)
                    ->sortable(),
                TextColumn::make('carbs_g')
                    ->label('C')
                    ->numeric(1)
                    ->sortable(),
                TextColumn::make('defaultUnit.code')
                    ->label('Unit')
                    ->placeholder('--'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('slug')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (IngredientCategory $record): string => $record->name)
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIngredients::route('/'),
            'create' => Pages\CreateIngredient::route('/create'),
            'edit' => Pages\EditIngredient::route('/{record}/edit'),
        ];
    }
}
