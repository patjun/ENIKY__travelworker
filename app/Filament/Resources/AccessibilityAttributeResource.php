<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccessibilityAttributeResource\Pages;
use App\Filament\Resources\AccessibilityAttributeResource\RelationManagers;
use App\Models\AccessibilityAttribute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccessibilityAttributeResource extends Resource
{
    protected static ?string $model = AccessibilityAttribute::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationLabel = 'Accessibility Attributes';

    protected static ?string $modelLabel = 'Accessibility Attribute';

    protected static ?string $pluralModelLabel = 'Accessibility Attributes';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationGroup = 'Places Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('placeholder')
                    ->label('Placeholder')
                    ->helperText('A short identifier for this attribute (e.g., wheelchair_accessible)')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('name_en')
                    ->label('English Name')
                    ->helperText('The display name in English')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('name_de')
                    ->label('German Name')
                    ->helperText('The display name in German')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description_en')
                    ->label('English Description')
                    ->helperText('A detailed description of what this attribute means')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('placeholder')
                    ->label('Placeholder')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label('English Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_de')
                    ->label('German Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description_en')
                    ->label('Description')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
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
            ->defaultSort('placeholder', 'asc');
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
            'index' => Pages\ListAccessibilityAttributes::route('/'),
            'create' => Pages\CreateAccessibilityAttribute::route('/create'),
            'edit' => Pages\EditAccessibilityAttribute::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Super Admin hat vollen Zugriff
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('view accessibility_attributes');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Super Admin hat vollen Zugriff
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('create accessibility_attributes');
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Super Admin hat vollen Zugriff
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Editor kann nicht löschen, aber bearbeiten
        if ($user->hasRole('editor')) {
            return $user->can('edit accessibility_attributes');
        }

        // Admin kann alles bearbeiten
        if ($user->hasRole('admin')) {
            return $user->can('edit accessibility_attributes');
        }

        return false;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Super Admin hat vollen Zugriff
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Editor kann nicht löschen
        if ($user->hasRole('editor')) {
            return false;
        }

        // Admin kann alles löschen
        if ($user->hasRole('admin')) {
            return $user->can('delete accessibility_attributes');
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Super Admin hat vollen Zugriff
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('view accessibility_attributes');
    }
}
