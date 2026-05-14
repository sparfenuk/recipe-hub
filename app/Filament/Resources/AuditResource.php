<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AuditResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $pluralModelLabel = 'Audit Log';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->default('System'),
                TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => Str::afterLast($state, '\\'))
                    ->sortable(),
                TextColumn::make('auditable_id')
                    ->label('ID'),
                TextColumn::make('old_values')
                    ->label('Old')
                    ->limit(50)
                    ->formatStateUsing(fn (mixed $state): string => is_array($state) ? json_encode($state, JSON_THROW_ON_ERROR) : (string) $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('new_values')
                    ->label('New')
                    ->limit(50)
                    ->formatStateUsing(fn (mixed $state): string => is_array($state) ? json_encode($state, JSON_THROW_ON_ERROR) : (string) $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('url')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                    ]),
                SelectFilter::make('auditable_type')
                    ->label('Model')
                    ->options(fn (): array => Audit::query()
                        ->distinct()
                        ->pluck('auditable_type')
                        ->mapWithKeys(fn (string $type): array => [$type => Str::afterLast($type, '\\')])
                        ->toArray()),
                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(fn (): array => User::query()
                        ->whereIn('id', Audit::query()->distinct()->pluck('user_id')->filter())
                        ->pluck('name', 'id')
                        ->toArray()),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudits::route('/'),
        ];
    }
}
