<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryResource\Pages;
use App\Models\Country;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Places Management';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name_de')
                    ->label('Name (DE)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_en')
                    ->label('Name (EN)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->label('Country Code')
                    ->required()
                    ->length(2)
                    ->helperText('ISO 3166-1 alpha-2 code (e.g., DE, AT, CH)')
                    ->unique(ignoreRecord: true)
                    ->formatStateUsing(fn (?string $state): string => $state ? strtoupper($state) : '')
                    ->dehydrateStateUsing(fn (?string $state): string => $state ? strtoupper($state) : ''),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_de')
                    ->label('Name (DE)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Name (EN)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cities_count')
                    ->counts('cities')
                    ->label('Cities')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => static::canDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false),
                ]),
            ])
            ->defaultSort('name_de');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view countries') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create countries') ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Editor kann nicht löschen, aber bearbeiten
        if ($user->hasRole('editor')) {
            return $user->can('edit countries');
        }

        // Super Admin und Admin können alles bearbeiten
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $user->can('edit countries');
        }

        return false;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Editor kann nicht löschen
        if ($user->hasRole('editor')) {
            return false;
        }

        // Super Admin und Admin können alles löschen
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $user->can('delete countries');
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view countries') ?? false;
    }
}
