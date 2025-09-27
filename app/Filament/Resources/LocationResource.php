<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use App\Jobs\ProcessDataForSeoOrchestrator;
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
use Illuminate\Support\HtmlString;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('place_id')
                    ->label('Google Places ID')
                    ->helperText(new HtmlString('<a href="https://developers.google.com/maps/documentation/places/web-service/place-id?hl=de" target="_blank" rel="noopener">Klick zum Place ID Finder</a>')),
                Forms\Components\Tabs::make('Languages')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('German')
                            ->label(fn (Get $get) => ($get('name') ?? 'Neue Location') . ' - DE')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->default('Neue Location')
                                    ->required()
                                    ->live(),
                                Forms\Components\TextInput::make('street')
                                    ->label('Street'),
                                Forms\Components\TextInput::make('zip')
                                    ->label('ZIP'),
                                Forms\Components\TextInput::make('city')
                                    ->label('City'),
                                Forms\Components\TextInput::make('country')
                                    ->label('Country'),
                                Forms\Components\Textarea::make('business_data')
                                    ->label('Business Data')
                                    ->disabled()
                                    ->columnSpanFull()
                                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : null)
                            ]),
                        Forms\Components\Tabs\Tab::make('English')
                            ->label(fn (Get $get) => ($get('en_name') ?? 'New Location') . ' - EN')
                            ->schema([
                                Forms\Components\TextInput::make('en_name')
                                    ->label('Name (EN)')
                                    ->live(),
                                Forms\Components\TextInput::make('en_street')
                                    ->label('Street (EN)'),
                                Forms\Components\TextInput::make('en_city')
                                    ->label('City (EN)'),
                                Forms\Components\TextInput::make('en_country')
                                    ->label('Country (EN)'),
                                Forms\Components\TextInput::make('en_phone')
                                    ->label('Phone (EN)'),
                                Forms\Components\TextInput::make('en_website')
                                    ->label('Website (EN)'),
                                Forms\Components\Textarea::make('en_description')
                                    ->label('Description (EN)')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('en_category')
                                    ->label('Category (EN)'),
                                Forms\Components\Textarea::make('en_opening_hours')
                                    ->label('Opening Hours (EN)')
                                    ->columnSpanFull()
                                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : null),
                                Forms\Components\Textarea::make('en_attributes')
                                    ->label('Attributes (EN)')
                                    ->columnSpanFull()
                                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : null),
                                Forms\Components\TextInput::make('en_main_image_url')
                                    ->label('Main Image URL (EN)'),
                                Forms\Components\TextInput::make('en_price_level')
                                    ->label('Price Level (EN)'),
                                Forms\Components\Textarea::make('en_additional_categories')
                                    ->label('Additional Categories (EN)')
                                    ->columnSpanFull()
                                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : null),
                            ]),
                        ]),
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->required(),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->required(),
                        Map::make('map')
                            ->label('Map')
                            ->columnSpanFull()
                            ->afterStateUpdated(function (Set $set, ?array $state): void {
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
                            ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                     ->searchable(),
                Tables\Columns\TextColumn::make('task_id')
                     ->label('Task ID (DE)')
                     ->searchable()
                     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('en_task_id')
                     ->label('Task ID (EN)')
                     ->searchable()
                     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('job_status')
                     ->label('Status (DE)')
                     ->badge()
                     ->color(fn (string $state): string => match ($state) {
                         'pending' => 'warning',
                         'processing' => 'info',
                         'completed' => 'success',
                         'failed' => 'danger',
                         'orchestrating' => 'info',
                         'posting_task' => 'info',
                         'task_posted' => 'warning',
                         'task_ready' => 'warning',
                         'getting_results' => 'info',
                         default => 'gray',
                     })
                     ->toggleable(),
                Tables\Columns\TextColumn::make('en_job_status')
                     ->label('Status (EN)')
                     ->badge()
                     ->color(fn (string $state): string => match ($state) {
                         'pending' => 'warning',
                         'processing' => 'info',
                         'completed' => 'success',
                         'failed' => 'danger',
                         'orchestrating' => 'info',
                         'posting_task' => 'info',
                         'task_posted' => 'warning',
                         'task_ready' => 'warning',
                         'getting_results' => 'info',
                         default => 'gray',
                     })
                     ->toggleable(),
                Tables\Columns\TextColumn::make('last_dataforseo_update')
                     ->label('Updated (DE)')
                     ->date('d.m.Y')
                     ->sortable()
                     ->toggleable(),
                Tables\Columns\TextColumn::make('en_last_dataforseo_update')
                     ->label('Updated (EN)')
                     ->date('d.m.Y')
                     ->sortable()
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
                    ->label('Update')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('DataForSEO Daten laden')
                    ->modalDescription('Möchten Sie die DataForSEO Daten für diese Location laden? Der Prozess wird im Hintergrund ausgeführt.')
                    ->modalSubmitActionLabel('Ja, in Queue einreihen')
                    ->visible(fn (Location $record) =>
                        !in_array($record->job_status, ['processing', 'posting_task', 'checking_ready', 'getting_results', 'orchestrating', 'task_posted', 'task_ready']) &&
                        !in_array($record->en_job_status, ['processing', 'posting_task', 'checking_ready', 'getting_results', 'orchestrating', 'task_posted', 'task_ready'])
                    )
                    ->action(function (Location $record) {
                        if (empty($record->place_id)) {
                            Notification::make()
                                ->title('Fehler')
                                ->body('Bitte geben Sie zuerst eine DataForSEO Location ID ein.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Dispatch job to queue
                        ProcessDataForSeoOrchestrator::dispatch($record);

                        $record->update(['job_status' => 'pending']);
                        $record->update(['en_job_status' => 'pending']);

                        Notification::make()
                            ->title('Job gestartet')
                            ->body('DataForSEO Request wurde in die Queue eingereiht. Der Prozess läuft im Hintergrund.')
                            ->success()
                            ->send();
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
