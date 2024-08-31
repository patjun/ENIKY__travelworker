<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KeywordResource\Actions\ExtractKeywordsAction;
use App\Filament\Resources\KeywordResource\Actions\GetKeywordForKeywordDataAction;
use App\Filament\Resources\KeywordResource\Pages;
use App\Models\Keyword;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KeywordResource extends Resource {
    protected static ?string $model = Keyword::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form( Form $form ): Form {
        return $form
            ->schema( [
                Forms\Components\TextInput::make( 'keyword' )
                                          ->required()
                                          ->maxLength( 255 ),

                Forms\Components\DateTimePicker::make( 'date' ),

                Forms\Components\Select::make( 'post_id' )
                                       ->label( 'Post' )
                                       ->options( Post::pluck( 'post_title', 'id' ) )
                                       ->searchable()
                                       ->nullable(),

                Forms\Components\Select::make( 'parents' )
                                       ->label( 'Parent Keywords' )
                                       ->multiple()
                                       ->relationship( 'parents', 'keyword' )
                                       ->options( Keyword::pluck( 'keyword', 'id' ) )
                                       ->searchable(),

                Forms\Components\Textarea::make( 'task_post_output' )
                                         ->columnSpanFull()
                                         ->json()
                                         ->rows( 6 ),

                Forms\Components\TextInput::make( 'task_id' )
                                          ->unique( ignoreRecord: true )
                                          ->maxLength( 255 ),

                Forms\Components\Textarea::make( 'task_get_output' )
                                         ->columnSpanFull()
                                         ->json()
                                         ->rows( 12 ),

                Forms\Components\Toggle::make( 'is_processed' )
                                       ->required(),
            ] );
    }

    public static function table( Table $table ): Table {
        return $table
            ->columns( [
                Tables\Columns\TextColumn::make( 'keyword' )
                                         ->searchable(),

                Tables\Columns\TextColumn::make( 'competition' )
                                         ->label( 'Wettbewerb' ),

                Tables\Columns\TextColumn::make( 'search_volume' )
                                         ->label( 'Anzahl' ),

                Tables\Columns\TextColumn::make( 'post.title' )
                                         ->searchable()
                                         ->state( function ( Keyword $record ): string {
                                             return $record->post?->title ?? 'No Post';
                                         } ),

                Tables\Columns\TextColumn::make( 'parents.keyword' )
                                         ->searchable()
                                         ->listWithLineBreaks()
                                         ->limitList( 3 ),

                Tables\Columns\IconColumn::make( 'is_processed' )
                                         ->boolean(),

                Tables\Columns\TextColumn::make( 'created_at' )
                                         ->dateTime()
                                         ->sortable()
                                         ->toggleable( isToggledHiddenByDefault: true ),

                Tables\Columns\TextColumn::make( 'updated_at' )
                                         ->dateTime()
                                         ->sortable()
                                         ->toggleable( isToggledHiddenByDefault: true ),
            ] )
            ->filters( [
                //
            ] )
            ->actions( [
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                GetKeywordForKeywordDataAction::make(),
            ] )
            ->bulkActions( [
                Tables\Actions\BulkActionGroup::make( [
                    Tables\Actions\DeleteBulkAction::make(),
                    ExtractKeywordsAction::make(),
                ] ),
            ] );
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index'  => Pages\ListKeywords::route( '/' ),
            'create' => Pages\CreateKeyword::route( '/create' ),
            'edit'   => Pages\EditKeyword::route( '/{record}/edit' ),
        ];
    }
}
