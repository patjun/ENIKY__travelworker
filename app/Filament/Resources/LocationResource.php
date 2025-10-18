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
use Illuminate\Support\HtmlString;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('place_id')
                ->label('Google Places ID')
                ->helperText(new HtmlString('<a href="https://developers.google.com/maps/documentation/places/web-service/place-id?hl=de" target="_blank" rel="noopener">Klick zum Place ID Finder</a>')),
            Forms\Components\Placeholder::make('global_widget_styles')
                ->label('')
                ->content(new HtmlString('
                    <style>
                    .widget{padding:15px;background-color:#fff;border-radius:16px;font-family:Arial,sans-serif;box-shadow:0 2px 8px rgba(0,0,0,.08);margin-bottom:20px;border:1px solid rgba(0,0,0,.05)}.widget .header{text-align:center;margin-bottom:15px;border-bottom:2px solid #186b29;padding-bottom:15px}.widget .header h3.title{margin:0;font-size:2.25rem;font-weight:bold;color:#186b29}.widget .widget-content{background:#fff;color:#374151}.widget .widget-content.contact{display:flex;flex-direction:column;gap:15px}.widget .widget-content.contact .item{display:flex;align-items:flex-start;gap:12px;padding:12px;background:rgba(113,191,68,.05);border-radius:16px}.widget .widget-content.contact .item .icon{font-size:20px;width:24px;text-align:center;flex-shrink:0;color:#71bf44}.widget .widget-content.contact .item .info{flex:1;line-height:1.4;font-size:1.75rem}.widget .widget-content.contact .item .line{margin-bottom:2px}.widget .widget-content.contact .item .link{color:#186b29;text-decoration:none;border-bottom:1px solid rgba(24,107,41,.3);transition:border-bottom-color .3s ease}.widget .widget-content.contact .item .link:hover{border-bottom-color:#186b29}.widget .widget-content.opening-hours .day{margin-bottom:8px;font-size:1.75rem;padding:8px 12px;background:rgba(113,191,68,.05);border-radius:16px}.widget .widget-content.opening-hours .day .timeline{display:flex;justify-content:space-between;margin-top:6px}.widget .widget-content.opening-hours .day .timeline.first{margin-top:0}.widget .widget-content.opening-hours .day .timeline .name{font-weight:bold;color:#186b29}.widget .widget-content.rating{display:flex;justify-content:space-between;align-items:center}.widget .widget-content.rating .score{display:flex;align-items:center;gap:15px}.widget .widget-content.rating .score .number{font-size:3rem;font-weight:bold;color:#186b29}.widget .widget-content.rating .score .details{display:flex;flex-direction:column;gap:5px}.widget .widget-content.rating .score .details .stars{display:flex;gap:2px}.widget .widget-content.rating .score .details .stars .star{font-size:20px}.widget .widget-content.rating .score .details .stars .star-full{color:orange}.widget .widget-content.rating .score .details .stars .star-half{color:orange}.widget .widget-content.rating .score .details .stars .star-empty{color:#d1d5db}.widget .widget-content.rating .score .details .text{font-size:1.75rem;color:#6b7280}.widget .widget-content.rating .reviews{text-align:right;display:flex;flex-direction:column;align-items:flex-end}.widget .widget-content.rating .reviews .count{font-size:2.25rem;font-weight:bold;color:#186b29}.widget .widget-content.rating .reviews .label{font-size:1.75rem;color:#6b7280}.widget .widget-content.accessibility{display:flex;flex-direction:column;gap:10px}.widget .widget-content.accessibility .item{display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:16px;background:rgba(113,191,68,.05)}.widget .widget-content.accessibility .item .status{font-size:1rem;width:20px;text-align:center;flex-shrink:0;color:#71bf44;font-weight:500}.widget .widget-content.accessibility .item .status.no{color:#ef4444}.widget .widget-content.accessibility .item .label{flex:1;font-size:1.75rem;font-weight:500}.widget .widget-content.accessibility .item.unavailable{opacity:.7}.knowledge_card_content .widget{padding:15px 0;border-radius:0px;box-shadow:none;margin-bottom:20px;border:none}
                    </style>
                ')),
            Forms\Components\TextInput::make('rating_value')
                ->label('Rating Value')
                ->numeric()
                ->minValue(0)
                ->maxValue(5)
                ->step(0.1),
            Forms\Components\TextInput::make('rating_votes_count')
                ->label('Rating Count')
                ->numeric()
                ->minValue(0)
                ->step(1),
            Forms\Components\Select::make('accessibilityAttributes')
                ->label('Accessibility Attributes')
                ->helperText('Select the accessibility features available at this location')
                ->multiple()
                ->relationship('accessibilityAttributes', 'name_en')
                ->searchable(['placeholder', 'name_en', 'name_de'])
                ->preload()
                ->columnSpanFull(),
            Forms\Components\Section::make('Opening Hours')
                ->description('Define opening hours that will be used for both German and English versions')
                ->collapsible()
                ->collapsed()
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Repeater::make('manual_opening_hours')
                        ->label('Time Slots')
                        ->schema([
                            Forms\Components\Select::make('days')
                                ->label('Days')
                                ->multiple()
                                ->options([
                                    'monday' => 'Monday / Montag',
                                    'tuesday' => 'Tuesday / Dienstag',
                                    'wednesday' => 'Wednesday / Mittwoch',
                                    'thursday' => 'Thursday / Donnerstag',
                                    'friday' => 'Friday / Freitag',
                                    'saturday' => 'Saturday / Samstag',
                                    'sunday' => 'Sunday / Sonntag',
                                ])
                                ->required()
                                ->columnSpan(1),
                            Forms\Components\TimePicker::make('open_time')
                                ->label('Opening Time')
                                ->seconds(false)
                                ->required()
                                ->columnSpan(1),
                            Forms\Components\TimePicker::make('close_time')
                                ->label('Closing Time')
                                ->seconds(false)
                                ->required()
                                ->columnSpan(1),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Add Time Slot')
                        ->reorderable()
                        ->collapsible()
                ]),
            Forms\Components\Tabs::make('Languages')
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('German')
                        ->label(fn (Get $get) => ($get('name') ?? 'Neue Location') . ' - DE')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Section::make('Grunddaten')
                                        ->columnSpan(1)
                                        ->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Name')
                                                ->default('Neue Location')
                                                ->required()
                                                ->live(),
                                            Forms\Components\Actions::make([
                                                Forms\Components\Actions\Action::make('search_address')
                                                    ->label('Adresse suchen')
                                                    ->icon('heroicon-o-magnifying-glass')
                                                    ->color('primary')
                                                    ->action(function (Set $set, Get $get) {
                                                        $searchQuery = $get('name');

                                                        if (empty($searchQuery)) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Fehler')
                                                                ->body('Bitte geben Sie einen Namen oder eine Adresse ein.')
                                                                ->warning()
                                                                ->send();
                                                            return;
                                                        }

                                                        // Use Nominatim (OpenStreetMap) for geocoding
                                                        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
                                                            'q' => $searchQuery,
                                                            'format' => 'json',
                                                            'limit' => 1,
                                                            'addressdetails' => 1,
                                                        ]);

                                                        $ch = curl_init();
                                                        curl_setopt($ch, CURLOPT_URL, $url);
                                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                        curl_setopt($ch, CURLOPT_USERAGENT, 'TravelWorker Location Finder');
                                                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                                                        $response = curl_exec($ch);
                                                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                                        curl_close($ch);

                                                        if ($httpCode === 200 && $response) {
                                                            $results = json_decode($response, true);

                                                            if (!empty($results) && isset($results[0])) {
                                                                $result = $results[0];
                                                                $lat = (float) $result['lat'];
                                                                $lng = (float) $result['lon'];

                                                                // Update coordinates
                                                                $set('latitude', $lat);
                                                                $set('longitude', $lng);
                                                                $set('map', ['lat' => $lat, 'lng' => $lng]);

                                                                // Update address fields
                                                                $address = $result['address'] ?? [];
                                                                if (isset($address['road'])) {
                                                                    $houseNumber = $address['house_number'] ?? '';
                                                                    $set('street', trim($address['road'] . ' ' . $houseNumber));
                                                                }
                                                                if (isset($address['postcode'])) {
                                                                    $set('zip', $address['postcode']);
                                                                }
                                                                if (isset($address['city']) || isset($address['town']) || isset($address['village'])) {
                                                                    $set('city', $address['city'] ?? $address['town'] ?? $address['village']);
                                                                }
                                                                if (isset($address['country'])) {
                                                                    $set('country', $address['country']);
                                                                }

                                                                \Filament\Notifications\Notification::make()
                                                                    ->title('Adresse gefunden')
                                                                    ->body('Die Adressdaten und Koordinaten wurden erfolgreich aktualisiert.')
                                                                    ->success()
                                                                    ->send();
                                                            } else {
                                                                \Filament\Notifications\Notification::make()
                                                                    ->title('Keine Ergebnisse')
                                                                    ->body('Für diese Suche wurden keine Ergebnisse gefunden.')
                                                                    ->warning()
                                                                    ->send();
                                                            }
                                                        } else {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Fehler')
                                                                ->body('Die Adresssuche ist fehlgeschlagen. Bitte versuchen Sie es erneut.')
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    }),
                                            ]),
                                            Forms\Components\TextInput::make('street')
                                                ->label('Street'),
                                            Forms\Components\TextInput::make('zip')
                                                ->label('ZIP'),
                                            Forms\Components\TextInput::make('city')
                                                ->label('City'),
                                            Forms\Components\TextInput::make('country')
                                                ->label('Country'),
                                            Forms\Components\TextInput::make('phone')
                                                ->label('Phone'),
                                            Forms\Components\TextInput::make('email')
                                                ->label('Email')
                                                ->email(),
                                            Forms\Components\TextInput::make('website')
                                                ->label('Website')
                                                ->url(),
                                            Forms\Components\TextInput::make('website_opening_hours')
                                                ->label('Website Opening Hours')
                                                ->url(),
                                            Forms\Components\TextInput::make('website_pricing')
                                                ->label('Website Pricing')
                                                ->url(),
                                        ]),
                                    Forms\Components\Section::make('Widgets')
                                        ->columnSpan(1)
                                        ->schema([
                                            Forms\Components\Placeholder::make('contact_info_preview')
                                                ->label('Contact Info Widget Preview')
                                                ->content(fn ($record) => $record && $record->contact_info_html
                                                    ? new HtmlString($record->contact_info_html)
                                                    : 'Widget wird nach dem Speichern generiert'
                                                ),
                                            Forms\Components\Placeholder::make('rating_preview')
                                                ->label('Rating Widget Preview')
                                                ->content(fn ($record) => $record && $record->rating_html
                                                    ? new HtmlString($record->rating_html)
                                                    : 'Widget wird nach dem Speichern generiert'
                                                ),
                                            Forms\Components\Placeholder::make('opening_hours_preview')
                                                ->label('Opening Hours Widget Preview')
                                                ->content(fn ($record) => $record && $record->opening_hours_html
                                                    ? new HtmlString($record->opening_hours_html)
                                                    : 'Widget wird nach dem Speichern generiert'
                                                ),
                                            Forms\Components\Placeholder::make('accessibility_preview')
                                                ->label('Accessibility Widget Preview')
                                                ->content(fn ($record) => $record && $record->accessibility_html
                                                    ? new HtmlString($record->accessibility_html)
                                                    : 'Widget wird nach dem Speichern generiert'
                                                ),
                                        ]),
                                ]),
                        ]),
                    Forms\Components\Tabs\Tab::make('English')
                        ->label(fn (Get $get) => ($get('en_name') ?? 'New Location') . ' - EN')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Section::make('Basic Data')
                                        ->columnSpan(1)
                                        ->schema([
                                            Forms\Components\TextInput::make('en_name')
                                                ->label('Name (EN)')
                                                ->live(),
                                            Forms\Components\TextInput::make('en_street')
                                                ->label('Street (EN)'),
                                            Forms\Components\TextInput::make('en_zip')
                                                ->label('ZIP (EN)'),
                                            Forms\Components\TextInput::make('en_city')
                                                ->label('City (EN)'),
                                            Forms\Components\TextInput::make('en_country')
                                                ->label('Country (EN)'),
                                            Forms\Components\TextInput::make('en_phone')
                                                ->label('Phone (EN)'),
                                            Forms\Components\TextInput::make('en_email')
                                                ->label('Email (EN)')
                                                ->email(),
                                            Forms\Components\TextInput::make('en_website')
                                                ->label('Website (EN)')
                                                ->url(),
                                            Forms\Components\TextInput::make('en_website_opening_hours')
                                                ->label('Website Opening Hours (EN)')
                                                ->url(),
                                            Forms\Components\TextInput::make('en_website_pricing')
                                                ->label('Website Pricing (EN)')
                                                ->url(),
                                        ]),
                                    Forms\Components\Section::make('Widgets')
                                        ->columnSpan(1)
                                        ->schema([
                                            Forms\Components\Placeholder::make('en_contact_info_preview')
                                                ->label('Contact Info Widget Preview')
                                                ->content(fn ($record) => $record && $record->en_contact_info_html
                                                    ? new HtmlString($record->en_contact_info_html)
                                                    : 'Widget will be generated after saving'
                                                ),
                                            Forms\Components\Placeholder::make('en_rating_preview')
                                                ->label('Rating Widget Preview')
                                                ->content(fn ($record) => $record && $record->en_rating_html
                                                    ? new HtmlString($record->en_rating_html)
                                                    : 'Widget will be generated after saving'
                                                ),
                                            Forms\Components\Placeholder::make('en_opening_hours_preview')
                                                ->label('Opening Hours Widget Preview')
                                                ->content(fn ($record) => $record && $record->en_opening_hours_html
                                                    ? new HtmlString($record->en_opening_hours_html)
                                                    : 'Widget will be generated after saving'
                                                ),
                                            Forms\Components\Placeholder::make('en_accessibility_preview')
                                                ->label('Accessibility Widget Preview')
                                                ->content(fn ($record) => $record && $record->en_accessibility_html
                                                    ? new HtmlString($record->en_accessibility_html)
                                                    : 'Widget will be generated after saving'
                                                ),
                                        ]),
                                ]),
                        ]),
                    ]),
                    Forms\Components\TextInput::make('latitude')
                        ->label('Latitude')
                        ->required()
                        ->live()
                        ->numeric()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            $lng = $get('longitude');
                            if ($state && $lng) {
                                $set('map', ['lat' => (float) $state, 'lng' => (float) $lng]);
                            }
                        }),
                    Forms\Components\TextInput::make('longitude')
                        ->label('Longitude')
                        ->required()
                        ->live()
                        ->numeric()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            $lat = $get('latitude');
                            if ($state && $lat) {
                                $set('map', ['lat' => (float) $lat, 'lng' => (float) $state]);
                            }
                        }),
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
                            } elseif (is_array($state) && isset($state['lat']) && isset($state['lng']) && $state['lat'] !== 0 && $state['lng'] !== 0) {
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
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('en_name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('en_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->width(300)
                    ->wrap(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
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
                Tables\Columns\TextColumn::make('task_id')
                     ->label('Task ID (DE)')
                     ->searchable()
                     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('en_task_id')
                     ->label('Task ID (EN)')
                     ->searchable()
                     ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('country')
                    ->label('Land')
                    ->options(function () {
                        return Location::whereNotNull('country')
                            ->distinct()
                            ->orderBy('country')
                            ->pluck('country', 'country')
                            ->toArray();
                    }),
                Tables\Filters\SelectFilter::make('city')
                    ->label('Stadt')
                    ->options(function () {
                        return Location::whereNotNull('city')
                            ->distinct()
                            ->orderBy('city')
                            ->pluck('city', 'city')
                            ->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('load_dataforseo')
                    ->label('Update')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Update')
                    ->modalDescription('Möchten Sie die DataForSEO Daten für diese Location laden? Der Prozess wird im Hintergrund ausgeführt.')
                    ->modalSubmitActionLabel('Ja, in Queue einreihen')
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
                    Tables\Actions\BulkAction::make('bulk_load_dataforseo')
                        ->label('Update')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Update für ausgewählte Locations')
                        ->modalDescription('Möchten Sie die Daten für alle ausgewählten Locations aktualisieren? Der Prozess wird im Hintergrund ausgeführt.')
                        ->modalSubmitActionLabel('Ja, alle in Queue einreihen')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $processed = 0;
                            $skipped = 0;
                            $errors = 0;

                            foreach ($records as $record) {
                                // Check if place_id is empty
                                if (empty($record->place_id)) {
                                    $errors++;
                                    continue;
                                }

                                // Check if job is already running
                                if (in_array($record->job_status, ['processing', 'posting_task', 'checking_ready', 'getting_results', 'orchestrating', 'task_posted', 'task_ready']) ||
                                    in_array($record->en_job_status, ['processing', 'posting_task', 'checking_ready', 'getting_results', 'orchestrating', 'task_posted', 'task_ready'])) {
                                    $skipped++;
                                    continue;
                                }

                                // Dispatch job to queue
                                ProcessDataForSeoOrchestrator::dispatch($record);
                                $record->update(['job_status' => 'pending']);
                                $record->update(['en_job_status' => 'pending']);
                                $processed++;
                            }

                            // Create notification based on results
                            $message = "Jobs gestartet: {$processed}";
                            if ($skipped > 0) {
                                $message .= ", übersprungen (bereits in Bearbeitung): {$skipped}";
                            }
                            if ($errors > 0) {
                                $message .= ", Fehler (keine Place ID): {$errors}";
                            }

                            Notification::make()
                                ->title('Bulk DataForSEO Update')
                                ->body($message)
                                ->success()
                                ->send();
                        }),
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
