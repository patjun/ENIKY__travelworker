<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Places Management';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('country_id')
                    ->label('Country')
                    ->relationship('country', 'name_de')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name_de')
                    ->label('Name (DE)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_en')
                    ->label('Name (EN)')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('country.name_de')
                    ->label('Country')
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
                Tables\Columns\TextColumn::make('attractions_count')
                    ->counts('attractions')
                    ->label('Attractions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country')
                    ->relationship('country', 'name_de')
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view cities') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create cities') ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Super Admin, Admin und Editor können alles bearbeiten
        if ($user->hasAnyRole(['super_admin', 'admin', 'editor'])) {
            return $user->can('edit cities') || $user->can('edit own cities');
        }

        // Prüfe ob User die Berechtigung "edit own cities" hat
        if ($user->can('edit own cities')) {
            // TODO: Wenn user_id Feld hinzugefügt wird, hier Prüfung auf eigene Cities einbauen
            // Aktuell können alle Benutzer mit "edit own cities" alle Cities bearbeiten,
            // bis das user_id Feld implementiert ist
            return true;
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
            return $user->can('delete cities');
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view cities') ?? false;
    }
}
