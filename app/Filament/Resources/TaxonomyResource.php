<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxonomyResource\Pages;
use App\Models\Taxonomy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaxonomyResource extends Resource
{
    protected static ?string $model = Taxonomy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('term_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('term_name')
                    ->required()
                    ->maxLength(200),
                Forms\Components\TextInput::make('term_taxonomy')
                    ->required()
                    ->maxLength(32),
                Forms\Components\TextInput::make('term_parent_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('term_count')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('website_id')
                    ->label('Website')
                    ->relationship('website', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('term_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('term_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('term_taxonomy')
                    ->searchable(),
                Tables\Columns\TextColumn::make('term_parent_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('term_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('website.name')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListTaxonomies::route('/'),
            'create' => Pages\CreateTaxonomy::route('/create'),
            'edit' => Pages\EditTaxonomy::route('/{record}/edit'),
        ];
    }
}
