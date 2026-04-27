<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('website_id')
                    ->label('Website')
                    ->relationship('website', 'name')
                    ->required(),
                Forms\Components\TextInput::make('url')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('website.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view pages') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create pages') ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Super Admin, Admin und Editor können alles bearbeiten
        if ($user->hasAnyRole(['super_admin', 'admin', 'editor'])) {
            return $user->can('edit pages');
        }

        // Author kann Pages bearbeiten
        if ($user->hasRole('author')) {
            return $user->can('edit pages');
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
            return $user->can('delete pages');
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
