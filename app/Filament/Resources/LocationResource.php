<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use App\Services\GooglePlacesService;
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
use Filament\Notifications\Notification;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Google Places Search')
                    ->schema([
                        Forms\Components\TextInput::make('search_query')
                            ->label('Search Places')
                            ->placeholder('Enter place name, address, or business...')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('search')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->action(function (Get $get, Set $set) {
                                        $query = $get('search_query');
                                        if (!$query) {
                                            Notification::make()
                                                ->title('Please enter a search query')
                                                ->warning()
                                                ->send();
                                            return;
                                        }

                                        try {
                                            $googlePlaces = new GooglePlacesService();
                                            $results = $googlePlaces->searchPlaces($query);
                                            
                                            if (empty($results)) {
                                                Notification::make()
                                                    ->title('No places found')
                                                    ->body('Try a different search term or check your Google Places API configuration.')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            // Use the first result and get detailed information
                                            $place = $results[0];
                                            if (isset($place['place_id'])) {
                                                $detailedPlace = $googlePlaces->getPlaceDetails($place['place_id']);
                                                if ($detailedPlace) {
                                                    $place = $detailedPlace;
                                                }
                                            }

                                            $locationData = $googlePlaces->extractLocationData($place);
                                            
                                            // Set all form fields with the data
                                            foreach ($locationData as $field => $value) {
                                                if ($value !== null) {
                                                    $set($field, $value);
                                                }
                                            }

                                            // Special handling for opening hours display
                                            if (isset($locationData['opening_hours']) && is_array($locationData['opening_hours'])) {
                                                $set('opening_hours_display', implode("\n", $locationData['opening_hours']));
                                            }

                                            // Update map
                                            if ($locationData['latitude'] && $locationData['longitude']) {
                                                $set('map', [
                                                    'lat' => $locationData['latitude'],
                                                    'lng' => $locationData['longitude']
                                                ]);
                                            }

                                            Notification::make()
                                                ->title('Location data imported successfully')
                                                ->body("Found: {$locationData['name']}")
                                                ->success()
                                                ->send();
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Error importing location data')
                                                ->body('Please check your Google Places API configuration and try again.')
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            )
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Forms\Components\Section::make('Basic Information')
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
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact & Details')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel(),
                        Forms\Components\TextInput::make('website')
                            ->label('Website')
                            ->url(),
                        Forms\Components\TextInput::make('category')
                            ->label('Category'),
                        Forms\Components\Select::make('price_level')
                            ->label('Price Level')
                            ->options([
                                0 => 'Free',
                                1 => 'Inexpensive',
                                2 => 'Moderate',
                                3 => 'Expensive',
                                4 => 'Very Expensive',
                            ]),
                        Forms\Components\TextInput::make('rating')
                            ->label('Rating')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(5),
                        Forms\Components\TextInput::make('entrance_fee')
                            ->label('Entrance Fee'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Opening Hours')
                    ->schema([
                        Forms\Components\Textarea::make('opening_hours_display')
                            ->label('Opening Hours')
                            ->placeholder('Opening hours will be populated from Google Places')
                            ->formatStateUsing(function ($state, $record) {
                                // For existing records, show the stored opening hours
                                if ($record && $record->opening_hours) {
                                    return is_array($record->opening_hours) 
                                        ? implode("\n", $record->opening_hours)
                                        : $record->opening_hours;
                                }
                                return '';
                            })
                            ->afterStateUpdated(function ($state, Set $set) {
                                // When manually edited, save to the actual opening_hours field
                                $hours = $state ? explode("\n", $state) : [];
                                $set('opening_hours', array_filter($hours));
                            })
                            ->live()
                            ->columnSpanFull(),
                        
                        Forms\Components\Hidden::make('opening_hours'),
                    ]),

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
                        } elseif ($state['lat'] !== 0 && $state['lng'] !== 0) {
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
                Tables\Columns\TextColumn::make('category')
                     ->searchable(),
                Tables\Columns\TextColumn::make('rating')
                     ->sortable()
                     ->badge()
                     ->color(fn (string $state): string => match (true) {
                         $state >= 4.5 => 'success',
                         $state >= 3.5 => 'warning',
                         default => 'danger',
                     }),
                Tables\Columns\TextColumn::make('price_level')
                     ->sortable()
                     ->formatStateUsing(fn (?int $state): string => match ($state) {
                         0 => 'Free',
                         1 => 'Inexpensive',
                         2 => 'Moderate',
                         3 => 'Expensive',
                         4 => 'Very Expensive',
                         default => 'Unknown',
                     }),
                Tables\Columns\TextColumn::make('phone')
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
