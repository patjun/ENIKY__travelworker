<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use App\Services\DataForSeoService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Dotswan\MapPicker\Fields\Map;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->default('Neue Location')
                    ->required(),
                Forms\Components\TextInput::make('street')
                    ->label('Street'),
                Forms\Components\TextInput::make('zip')
                    ->label('ZIP'),
                Forms\Components\TextInput::make('city')
                    ->label('City'),
                Forms\Components\TextInput::make('country')
                    ->label('Country'),
                Forms\Components\TextInput::make('latitude')
                    ->label('Latitude')
                    ->required(),
                Forms\Components\TextInput::make('longitude')
                    ->label('Longitude')
                    ->required(),
                Forms\Components\TextInput::make('place_id')
                    ->label('DataForSEO Location ID')
                    ->helperText('Die Location ID für DataForSEO API Abfragen'),
                Map::make('map')
                   ->label('Map')
                   ->columnSpanFull()
                   ->afterStateUpdated(function (Get $get, Set $set, string|array|null $old, ?array $state): void {
                       $set('latitude', $state['lat']);
                       $set('longitude', $state['lng']);
                   })
                    ->afterStateHydrated(function ($state, $record, Set $set): void {
                        // ray()->clearAll();
                        // ray($state, $record);

                        if (!is_null($record)){
                            // ray('using record');
                            $set('map', ['lat' => $record->latitude, 'lng' => $record->longitude]);
                        } elseif ($state['lat'] !== 0 && $state['lng'] !== 0) {
                            // ray('using state');
                            $set('map', ['lat' => $state['lat'], 'lng' => $state['lng']]);
                        } else {
                            // ray('using default');
                            $set('map', ['lat' => 52.520008, 'lng' => 13.404954]);
                        }
                    })
                   ->liveLocation()
                   ->showMarker()
                   ->markerColor("#22c55eff")
                   ->showFullscreenControl()
                   ->showZoomControl()
                   ->draggable()
                   ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")
                   ->zoom(13)
                   ->detectRetina()
                   // ->showMyLocationButton()
                   ->extraTileControl([])
                   ->extraControl([
                       'zoomDelta'           => 1,
                       'zoomSnap'            => 2,
                   ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                     ->searchable(),
                Tables\Columns\TextColumn::make('place_id')
                     ->label('DataForSEO ID')
                     ->searchable()
                     ->toggleable(),
                Tables\Columns\TextColumn::make('task_id')
                     ->label('Task ID')
                     ->searchable()
                     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_dataforseo_update')
                     ->label('Letztes Update')
                     ->dateTime()
                     ->sortable()
                     ->toggleable(),
                Tables\Columns\IconColumn::make('business_data')
                     ->label('Business Data')
                     ->boolean()
                     ->getStateUsing(fn ($record) => !empty($record->business_data))
                     ->toggleable(),
                Tables\Columns\TextColumn::make('city')
                     ->searchable()
                     ->toggleable(),
                Tables\Columns\TextColumn::make('country')
                     ->searchable()
                     ->toggleable(),
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
                Tables\Actions\Action::make('load_dataforseo')
                    ->label('DataForSEO laden')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('DataForSEO Daten laden')
                    ->modalDescription('Möchten Sie die DataForSEO Daten für diese Location laden?')
                    ->modalSubmitActionLabel('Ja, laden')
                    ->action(function (Location $record) {
                        if (empty($record->place_id)) {
                            Notification::make()
                                ->title('Fehler')
                                ->body('Bitte geben Sie zuerst eine DataForSEO Location ID ein.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $dataForSeoService = new DataForSeoService();
                        $result = $dataForSeoService->getBusinessData(
                            $record->place_id,
                            $record->language_code ?? 'de',
                            $record->location_code ?? 2276
                        );

                        if (isset($result['error'])) {
                            Notification::make()
                                ->title('DataForSEO API Fehler')
                                ->body($result['error'])
                                ->danger()
                                ->send();
                        } else {
                            $record->update([
                                'task_post_output' => $result,
                                'last_dataforseo_update' => now(),
                            ]);

                            if (isset($result['tasks'][0]['id'])) {
                                $record->update(['task_id' => $result['tasks'][0]['id']]);
                            }

                            Notification::make()
                                ->title('Erfolgreich')
                                ->body('DataForSEO Daten wurden geladen und gespeichert.')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
