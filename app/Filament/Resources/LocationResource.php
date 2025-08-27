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
                Forms\Components\Textarea::make('business_data')
                    ->label('Business Data (JSON)')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            return $decoded ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state;
                        }
                        return $state;
                    })
                    ->disabled()
                    ->rows(10)
                    ->columnSpanFull(),
                Forms\Components\Section::make('Dataforseo Settings')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('place_id')
                                    ->label('Google Place ID')
                                    ->helperText('Format: ChIJN1t_tDeuEmsRUsoyG83frY4'),
                                Forms\Components\TextInput::make('cid')
                                    ->label('Google CID')
                                    ->helperText('Format: cid:194604053573767737'),
                            ]),
                        Forms\Components\Select::make('location_code')
                            ->label('Location Code')
                            ->options([
                                2276 => '2276 - Germany',
                                2840 => '2840 - United States',
                                2756 => '2756 - Switzerland',
                                2040 => '2040 - Austria',
                            ])
                            ->default(2276)
                            ->searchable(),
                        Forms\Components\Select::make('language_code')
                            ->label('Language Code')
                            ->options([
                                'de' => 'Deutsch',
                                'en' => 'English',
                                'fr' => 'Français',
                                'it' => 'Italiano',
                                'es' => 'Español',
                            ])
                            ->default('de'),
                        Forms\Components\DateTimePicker::make('last_dataforseo_update')
                            ->label('Last Update')
                            ->disabled(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Map::make('map')
                   ->label('Map')
                   ->columnSpanFull()
                   ->afterStateUpdated(function (Get $get, Set $set, string|array|null $old, ?array $state): void {
                       $set('latitude', $state['lat']);
                       $set('longitude', $state['lng']);
                   })
                    ->afterStateHydrated(function ($state, $record, Set $set): void {
                        if (!is_null($record)){
                            $set('map', ['lat' => $record->latitude, 'lng' => $record->longitude]);
                        } elseif (is_array($state) && isset($state['lat']) && isset($state['lng']) && $state['lat'] !== 0 && $state['lng'] !== 0) {
                            $set('map', ['lat' => $state['lat'], 'lng' => $state['lng']]);
                        } else {
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
                Tables\Columns\TextColumn::make('city')
                     ->searchable(),
                Tables\Columns\TextColumn::make('cid')
                     ->label('Google CID')
                     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_dataforseo_update')
                     ->label('Last API Update')
                     ->dateTime()
                     ->sortable()
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
                Tables\Actions\Action::make('fetch_business_data')
                    ->label('Update Business Data')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Location $record) {
                        if (empty($record->cid)) {
                            return;
                        }

                        $dataForSeoService = app(DataForSeoService::class);
                        $result = $dataForSeoService->getMyBusinessInfo(
                            $record->cid,
                            $record->location_code ?? 2276,
                            $record->language_code ?? 'de',
                            $record->place_id
                        );

                        $record->update([
                            'business_data' => $result,
                            'last_dataforseo_update' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Location $record) => !empty($record->cid)),
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
