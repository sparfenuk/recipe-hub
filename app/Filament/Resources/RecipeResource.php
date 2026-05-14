<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecipeResource\Pages;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Tag;
use App\Models\Unit;
use Carbon\Carbon;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class RecipeResource extends Resource
{
    use Translatable;

    protected static ?string $model = Recipe::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic info')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(150)
                            ->unique(ignoreRecord: true),
                        Textarea::make('summary')
                            ->maxLength(500)
                            ->rows(2)
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->columnSpanFull(),
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'slug')
                            ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->name)
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('cuisine_id')
                            ->label('Cuisine')
                            ->relationship('cuisine', 'slug')
                            ->getOptionLabelFromRecordUsing(fn (Cuisine $record): string => $record->name)
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('difficulty')
                            ->options([
                                'easy' => 'Easy',
                                'medium' => 'Medium',
                                'hard' => 'Hard',
                            ])
                            ->default('medium')
                            ->required(),
                        TextInput::make('servings')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(1)
                            ->required(),
                        TextInput::make('prep_time_min')
                            ->label('Prep time (min)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('cook_time_min')
                            ->label('Cook time (min)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'review' => 'Review',
                                'published' => 'Published',
                                'archived' => 'Archived',
                            ])
                            ->default('draft')
                            ->required(),
                        Toggle::make('is_featured')
                            ->default(false),
                    ]),

                Section::make('Media')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('hero')
                            ->collection('hero')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('gallery')
                            ->collection('gallery')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(10)
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ]),

                Section::make('Ingredients')
                    ->schema([
                        Repeater::make('recipeIngredients')
                            ->relationship()
                            ->orderColumn('position')
                            ->columns(4)
                            ->schema([
                                Select::make('ingredient_id')
                                    ->label('Ingredient')
                                    ->relationship(
                                        name: 'ingredient',
                                        titleAttribute: 'slug',
                                        modifyQueryUsing: fn ($query) => $query->where('is_active', true)
                                            ->orderBy('name->'.app()->getLocale()),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (Ingredient $record): string => $record->name)
                                    ->getSearchResultsUsing(fn (string $search): array => Ingredient::query()
                                        ->where('is_active', true)
                                        ->where(function ($q) use ($search): void {
                                            $q->where('name->en', 'like', "%{$search}%")
                                                ->orWhere('name->uk', 'like', "%{$search}%");
                                        })
                                        ->orderBy('name->'.app()->getLocale())
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn (Ingredient $i): array => [$i->id => $i->name])
                                        ->all())
                                    ->searchable()
                                    ->preload(false)
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('amount')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.001)
                                    ->required(),
                                Select::make('unit_id')
                                    ->label('Unit')
                                    ->options(fn () => Unit::query()
                                        ->orderBy('code')
                                        ->get()
                                        ->mapWithKeys(fn (Unit $u) => [$u->id => $u->name])
                                        ->all())
                                    ->searchable()
                                    ->required(),
                                TextInput::make('note')
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('group_label')
                                    ->label('Group')
                                    ->maxLength(100)
                                    ->placeholder('e.g., For the sauce'),
                                Toggle::make('is_optional')
                                    ->inline(false)
                                    ->default(false),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add ingredient')
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => ($state['ingredient_id'] ?? null)
                                ? Ingredient::find($state['ingredient_id'])?->name
                                : null),
                    ]),

                Section::make('Steps')
                    ->schema([
                        Repeater::make('steps')
                            ->relationship()
                            ->orderColumn('position')
                            ->schema([
                                Textarea::make('body')
                                    ->label('Instruction')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull(),
                                SpatieMediaLibraryFileUpload::make('step_photo')
                                    ->collection('step_photo')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(5120)
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add step')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => isset($state['body'])
                                ? Str::limit($state['body'], 60)
                                : null),
                    ]),

                Section::make('Tags')
                    ->schema([
                        Select::make('tags')
                            ->relationship('tags', 'slug')
                            ->getOptionLabelFromRecordUsing(fn (Tag $record): string => $record->name)
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),

                Section::make('Nutrition')
                    ->visible(fn (?Recipe $record): bool => $record !== null)
                    ->description(function (?Recipe $record): string {
                        if ($record === null || $record->nutrition_cached_at === null) {
                            return 'Save the recipe with ingredients to compute nutrition';
                        }

                        /** @var Carbon $cachedAt */
                        $cachedAt = $record->nutrition_cached_at;

                        return 'Last computed '.$cachedAt->diffForHumans();
                    })
                    ->schema([
                        Fieldset::make('Per serving')
                            ->columns(5)
                            ->schema([
                                Placeholder::make('kcal_per_serving_display')
                                    ->label('kcal')
                                    ->content(fn (Recipe $record): string => number_format((float) $record->kcal_per_serving, 0)),
                                Placeholder::make('protein_per_serving_display')
                                    ->label('Protein (g)')
                                    ->content(fn (Recipe $record): string => number_format((float) $record->protein_per_serving_g, 1)),
                                Placeholder::make('fat_per_serving_display')
                                    ->label('Fat (g)')
                                    ->content(fn (Recipe $record): string => number_format((float) $record->fat_per_serving_g, 1)),
                                Placeholder::make('carbs_per_serving_display')
                                    ->label('Carbs (g)')
                                    ->content(fn (Recipe $record): string => number_format((float) $record->carbs_per_serving_g, 1)),
                                Placeholder::make('fiber_per_serving_display')
                                    ->label('Fiber (g)')
                                    ->content(fn (Recipe $record): string => number_format((float) $record->fiber_per_serving_g, 1)),
                            ]),
                        Fieldset::make('Total')
                            ->columns(5)
                            ->schema([
                                Placeholder::make('total_kcal_display')
                                    ->label('kcal')
                                    ->content(fn (Recipe $record): string => number_format((float) $record->total_kcal, 0)),
                                Placeholder::make('total_protein_display')
                                    ->label('Protein (g)')
                                    ->content(fn (Recipe $record): string => number_format((float) $record->total_protein_g, 1)),
                                Placeholder::make('total_fat_display')
                                    ->label('Fat (g)')
                                    ->content(fn (Recipe $record): string => number_format((float) $record->total_fat_g, 1)),
                                Placeholder::make('total_carbs_display')
                                    ->label('Carbs (g)')
                                    ->content(fn (Recipe $record): string => number_format((float) $record->total_carbs_g, 1)),
                                Placeholder::make('total_fiber_display')
                                    ->label('Fiber (g)')
                                    ->content(fn (Recipe $record): string => number_format((float) $record->total_fiber_g, 1)),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('hero')
                    ->collection('hero')
                    ->conversion('thumb')
                    ->circular()
                    ->defaultImageUrl(url('/images/recipe-placeholder.svg'))
                    ->label(''),
                TextColumn::make('title')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q->where('title->en', 'like', "%{$search}%")
                                ->orWhere('title->uk', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('title->'.app()->getLocale(), $direction))
                    ->limit(40),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'review' => 'warning',
                        'published' => 'success',
                        'archived' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('--')
                    ->toggleable(),
                TextColumn::make('cuisine.name')
                    ->label('Cuisine')
                    ->placeholder('--')
                    ->toggleable(),
                TextColumn::make('kcal_per_serving')
                    ->label('kcal/srv')
                    ->numeric(0)
                    ->sortable(),
                TextColumn::make('servings')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('difficulty')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'review' => 'Review',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->name)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('cuisine_id')
                    ->label('Cuisine')
                    ->relationship('cuisine', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (Cuisine $record): string => $record->name)
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->requiresConfirmation()
                    ->action(fn (Recipe $record) => static::duplicateRecipe($record)),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                /** @var Recipe $record */
                                $record->update([
                                    'status' => 'published',
                                    'published_at' => $record->published_at ?? now(),
                                ]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('archive')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                /** @var Recipe $record */
                                $record->update(['status' => 'archived']);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                /** @var Recipe $record */
                                static::duplicateRecipe($record);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function duplicateRecipe(Recipe $recipe): Recipe
    {
        $recipe->loadMissing('recipeIngredients', 'steps', 'tags', 'media');

        $clone = $recipe->replicate();

        foreach ($recipe->getTranslations('title') as $locale => $value) {
            $clone->setTranslation('title', $locale, $value.' (Copy)');
        }

        $clone->status = 'draft';
        $clone->published_at = null;
        $clone->nutrition_cached_at = null;

        $baseSlug = Str::slug($recipe->getTranslation('title', 'en', false) ?: $recipe->getTranslation('title', 'uk'));
        $baseSlug = $baseSlug !== '' ? $baseSlug.'-copy' : 'copy';
        $slug = $baseSlug;
        $counter = 1;

        while (Recipe::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.++$counter;
        }

        $clone->slug = $slug;
        $clone->save();

        foreach ($recipe->recipeIngredients as $ri) {
            $clone->recipeIngredients()->create($ri->only([
                'ingredient_id', 'position', 'amount', 'unit_id',
                'grams_override', 'note', 'is_optional', 'group_label',
            ]));
        }

        foreach ($recipe->steps as $step) {
            $clone->steps()->create([
                'position' => $step->position,
                'body' => $step->getTranslations('body'),
            ]);
        }

        $clone->tags()->sync($recipe->tags->pluck('id'));

        foreach ($recipe->media as $media) {
            $media->copy($clone, $media->collection_name);
        }

        return $clone;
    }

    /** @return Builder<Recipe> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /** @return array<string, mixed> */
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecipes::route('/'),
            'create' => Pages\CreateRecipe::route('/create'),
            'edit' => Pages\EditRecipe::route('/{record}/edit'),
        ];
    }
}
