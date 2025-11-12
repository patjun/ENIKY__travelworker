<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttractionResource\Pages;
use App\Jobs\ProcessDataForSeoOrchestrator;
use App\Models\Attraction;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AttractionResource extends Resource
{
    protected static ?string $model = Attraction::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Places Management';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Placeholder::make('global_widget_styles')
                ->label('')
                ->columnSpanFull()
                ->content(new HtmlString('
                    <style>
                    .widget{padding:15px;background-color:#fff;border-radius:16px;font-family:Arial,sans-serif;box-shadow:0 2px 8px rgba(0,0,0,.08);margin-bottom:20px;border:1px solid rgba(0,0,0,.05)}.widget .header{text-align:center;margin-bottom:15px;border-bottom:2px solid #186b29;padding-bottom:15px}.widget .header h3.title{margin:0;font-size:2.25rem;font-weight:bold;color:#186b29}.widget .widget-content{background:#fff;color:#374151}.widget .widget-content.contact{display:flex;flex-direction:column;gap:15px}.widget .widget-content.contact .item{display:flex;align-items:flex-start;gap:12px;padding:12px;background:rgba(113,191,68,.05);border-radius:16px}.widget .widget-content.contact .item .icon{font-size:20px;width:24px;text-align:center;flex-shrink:0;color:#71bf44}.widget .widget-content.contact .item .info{flex:1;line-height:1.4;font-size:1.75rem}.widget .widget-content.contact .item .line{margin-bottom:2px}.widget .widget-content.contact .item .link{color:#186b29;text-decoration:none;border-bottom:1px solid rgba(24,107,41,.3);transition:border-bottom-color .3s ease}.widget .widget-content.contact .item .link:hover{border-bottom-color:#186b29}.widget .widget-content.opening-hours .season-block{margin-bottom:15px}.widget .widget-content.opening-hours .season-header,.widget .widget-content.opening-hours summary{font-size:1.4rem;font-weight:600;color:#fff;background-color:#186b29;cursor:pointer;padding:8px 12px 8px 36px;margin-bottom:8px;border-radius:8px;list-style:none;position:relative}.widget .widget-content.opening-hours .season-header::-webkit-details-marker,.widget .widget-content.opening-hours summary::-webkit-details-marker{display:none}.widget .widget-content.opening-hours .season-header::marker,.widget .widget-content.opening-hours summary::marker{display:none}.widget .widget-content.opening-hours .season-header::before,.widget .widget-content.opening-hours summary::before{content:"▶";color:#fff;position:absolute;left:12px;top:50%;transform:translateY(-50%);transition:transform 0.2s ease;font-size:0.8em}.widget .widget-content.opening-hours details[open] .season-header::before,.widget .widget-content.opening-hours details[open] summary::before{transform:translateY(-50%) rotate(90deg)}.widget .widget-content.opening-hours .day{margin-bottom:8px;font-size:1.75rem;padding:8px 12px;background:rgba(113,191,68,.05);border-radius:16px}.widget .widget-content.opening-hours .day .timeline{display:flex;justify-content:space-between;margin-top:6px}.widget .widget-content.opening-hours .day .timeline.first{margin-top:0}.widget .widget-content.opening-hours .day .timeline .name{font-weight:bold;color:#186b29}.widget .widget-content.rating{display:flex;justify-content:space-between;align-items:center}.widget .widget-content.rating .score{display:flex;align-items:center;gap:15px}.widget .widget-content.rating .score .number{font-size:3rem;font-weight:bold;color:#186b29}.widget .widget-content.rating .score .details{display:flex;flex-direction:column;gap:5px}.widget .widget-content.rating .score .details .stars{display:flex;gap:2px}.widget .widget-content.rating .score .details .stars .star{font-size:20px}.widget .widget-content.rating .score .details .stars .star-full{color:orange}.widget .widget-content.rating .score .details .stars .star-half{color:orange}.widget .widget-content.rating .score .details .stars .star-empty{color:#d1d5db}.widget .widget-content.rating .score .details .text{font-size:1.75rem;color:#6b7280}.widget .widget-content.rating .reviews{text-align:right;display:flex;flex-direction:column;align-items:flex-end}.widget .widget-content.rating .reviews .count{font-size:2.25rem;font-weight:bold;color:#186b29}.widget .widget-content.rating .reviews .label{font-size:1.75rem;color:#6b7280}.widget .widget-content.accessibility{display:flex;flex-direction:column;gap:10px}.widget .widget-content.accessibility .item{display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:16px;background:rgba(113,191,68,.05)}.widget .widget-content.accessibility .item .status{font-size:1rem;width:20px;text-align:center;flex-shrink:0;color:#71bf44;font-weight:500}.widget .widget-content.accessibility .item .status.no{color:#ef4444}.widget .widget-content.accessibility .item .label{flex:1;font-size:1.75rem;font-weight:500}.widget .widget-content.accessibility .item.unavailable{opacity:.7}.knowledge_card_content .widget{padding:15px 0;border-radius:0px;box-shadow:none;margin-bottom:20px;border:none}
                    </style>
                ')),
            Forms\Components\Tabs::make('Languages')
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('English')
                        ->label(fn (Get $get) => ($get('en_name') ?? 'New Attraction').' - EN')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Section::make('Basic Data')
                                        ->columnSpan(1)
                                        ->schema([
                                            Forms\Components\TextInput::make('en_name')
                                                ->label('Name (EN)')
                                                ->required()
                                                ->live(),
                                            Forms\Components\TextInput::make('en_website')
                                                ->label('Website (EN)')
                                                ->required()
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
                    Forms\Components\Tabs\Tab::make('German')
                        ->label(fn (Get $get) => ($get('name') ?? 'Neue Attraktion').' - DE')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Section::make('Grunddaten')
                                        ->columnSpan(1)
                                        ->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Name')
                                                ->default('Neue Attraktion')
                                                ->required()
                                                ->live(),
                                            Forms\Components\TextInput::make('website')
                                                ->required()
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
                ]),
            Forms\Components\Select::make('city_id')
                ->label('City')
                ->relationship('city', 'name_de')
                ->searchable(['name_de', 'name_en'])
                ->preload()
                ->required()
                ->createOptionForm([
                    Forms\Components\Select::make('country_id')
                        ->label('Country')
                        ->relationship('country', 'name_de')
                        ->required()
                        ->searchable(['name_de', 'name_en'])
                        ->preload(),
                    Forms\Components\TextInput::make('name_de')
                        ->label('Name (DE)')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('name_en')
                        ->label('Name (EN)')
                        ->required()
                        ->maxLength(255),
                ]),
            Forms\Components\Actions::make([
                Forms\Components\Actions\Action::make('search_address')
                    ->label('Search Address')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('primary')
                    ->action(function (Set $set, Get $get) {
                        $searchQuery = $get('en_name');

                        if (empty($searchQuery)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Please enter a name or address.')
                                ->warning()
                                ->send();

                            return;
                        }

                        // Use Nominatim (OpenStreetMap) for geocoding
                        $url = 'https://nominatim.openstreetmap.org/search?'.http_build_query([
                            'q' => $searchQuery,
                            'format' => 'json',
                            'limit' => 1,
                            'addressdetails' => 1,
                            'accept-language' => 'en',
                        ]);

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_USERAGENT, 'TravelWorker Attraction Finder');
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($httpCode === 200 && $response) {
                            $results = json_decode($response, true);

                            if (! empty($results) && isset($results[0])) {
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
                                    $set('street', trim($address['road'].' '.$houseNumber));
                                }
                                if (isset($address['postcode'])) {
                                    $set('zip', $address['postcode']);
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('Address Found')
                                    ->body('Address data and coordinates have been successfully updated.')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Results')
                                    ->body('No results were found for this search.')
                                    ->warning()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Address search failed. Please try again.')
                                ->danger()
                                ->send();
                        }
                    }),
            ]),
            Forms\Components\TextInput::make('street')
                ->required()
                ->label('Street'),
            Forms\Components\TextInput::make('zip')
                ->required()
                ->label('ZIP'),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email(),
            Forms\Components\TextInput::make('rating_value')
                ->label('Rating Value')
                ->numeric()
                ->required()
                ->minValue(0)
                ->maxValue(5)
                ->step(0.1),
            Forms\Components\TextInput::make('rating_votes_count')
                ->label('Rating Count')
                ->numeric()
                ->required()
                ->minValue(0)
                ->step(1),
            Forms\Components\Select::make('accessibilityAttributes')
                ->label('Accessibility Attributes')
                ->helperText('Select the accessibility features available at this attraction')
                ->multiple()
                ->relationship('accessibilityAttributes', 'name_en')
                ->searchable(['placeholder', 'name_en', 'name_de'])
                ->preload()
                ->columnSpanFull(),
            Forms\Components\Section::make('Opening Hours')
                ->description('Define seasonal opening hours that will be used for both German and English versions')
                ->collapsible()
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Repeater::make('manual_opening_hours')
                        ->label('Seasons')
                        ->schema([
                            Forms\Components\Placeholder::make('year_round_notice')
                                ->label('')
                                ->content(function (Get $get) {
                                    $allSeasons = $get('../../manual_opening_hours') ?? [];
                                    $hasYearRound = false;
                                    
                                    foreach ($allSeasons as $season) {
                                        if (isset($season['is_year_round']) && $season['is_year_round']) {
                                            $hasYearRound = true;
                                            break;
                                        }
                                    }
                                    
                                    if ($hasYearRound && count($allSeasons) > 1) {
                                        return new HtmlString('⚠️ <strong>Notice:</strong> You have a Year-Round season. It doesn\'t make sense to have multiple seasons when one is year-round. Consider removing either the year-round season or the seasonal ones.');
                                    }
                                    
                                    return '';
                                })
                                ->visible(function (Get $get) {
                                    $allSeasons = $get('../../manual_opening_hours') ?? [];
                                    $hasYearRound = false;
                                    
                                    foreach ($allSeasons as $season) {
                                        if (isset($season['is_year_round']) && $season['is_year_round']) {
                                            $hasYearRound = true;
                                            break;
                                        }
                                    }
                                    
                                    return $hasYearRound && count($allSeasons) > 1;
                                })
                                ->extraAttributes(['class' => 'text-warning-600 text-sm']),
                            Forms\Components\Grid::make(4)
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Season Name (Optional)')
                                        ->placeholder('e.g. Winter Season, Summer Hours')
                                        ->columnSpan(4),
                                    Forms\Components\Toggle::make('is_year_round')
                                        ->label('Year-Round')
                                        ->helperText(function (Get $get) {
                                            $allSeasons = $get('../../manual_opening_hours') ?? [];
                                            $seasonCount = count($allSeasons);
                                            
                                            if ($seasonCount > 1) {
                                                return '⚠️ Year-Round is only allowed when you have a single season. Remove other seasons first.';
                                            }
                                            
                                            return 'This season applies all year (repeats every year)';
                                        })
                                        ->default(false)
                                        ->reactive()
                                        ->disabled(function (Get $get) {
                                            $allSeasons = $get('../../manual_opening_hours') ?? [];
                                            $seasonCount = count(array_filter($allSeasons, function($season) {
                                                return !empty($season); // Filter out empty entries
                                            }));
                                            
                                            // Disable if there's more than one season
                                            return $seasonCount > 1;
                                        })
                                        ->columnSpan(4),
                                    
                                    // Start Date
                                    Forms\Components\Select::make('start_month')
                                        ->label('Start Month')
                                        ->options([
                                            '01' => 'January / Januar',
                                            '02' => 'February / Februar',
                                            '03' => 'March / März',
                                            '04' => 'April / April',
                                            '05' => 'May / Mai',
                                            '06' => 'June / Juni',
                                            '07' => 'July / Juli',
                                            '08' => 'August / August',
                                            '09' => 'September / September',
                                            '10' => 'October / Oktober',
                                            '11' => 'November / November',
                                            '12' => 'December / Dezember',
                                        ])
                                        ->hidden(fn (Get $get) => $get('is_year_round'))
                                        ->required(fn (Get $get) => ! $get('is_year_round'))
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            static::updateStartDate($get, $set);
                                            static::checkSeasonOverlaps($get, $set);
                                        })
                                        ->dehydrated(false)
                                        ->columnSpan(1),
                                    Forms\Components\Select::make('start_day')
                                        ->label('Start Day')
                                        ->options(fn () => array_combine(
                                            array_map(fn($i) => sprintf('%02d', $i), range(1, 31)),
                                            range(1, 31)
                                        ))
                                        ->hidden(fn (Get $get) => $get('is_year_round'))
                                        ->required(fn (Get $get) => ! $get('is_year_round'))
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            static::updateStartDate($get, $set);
                                            static::checkSeasonOverlaps($get, $set);
                                        })
                                        ->dehydrated(false)
                                        ->columnSpan(1),
                                    
                                    // End Date
                                    Forms\Components\Select::make('end_month')
                                        ->label('End Month')
                                        ->options([
                                            '01' => 'January / Januar',
                                            '02' => 'February / Februar',
                                            '03' => 'March / März',
                                            '04' => 'April / April',
                                            '05' => 'May / Mai',
                                            '06' => 'June / Juni',
                                            '07' => 'July / Juli',
                                            '08' => 'August / August',
                                            '09' => 'September / September',
                                            '10' => 'October / Oktober',
                                            '11' => 'November / November',
                                            '12' => 'December / Dezember',
                                        ])
                                        ->hidden(fn (Get $get) => $get('is_year_round'))
                                        ->required(fn (Get $get) => ! $get('is_year_round'))
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            static::updateEndDate($get, $set);
                                            static::checkSeasonOverlaps($get, $set);
                                        })
                                        ->dehydrated(false)
                                        ->columnSpan(1),
                                    Forms\Components\Select::make('end_day')
                                        ->label('End Day')
                                        ->options(fn () => array_combine(
                                            array_map(fn($i) => sprintf('%02d', $i), range(1, 31)),
                                            range(1, 31)
                                        ))
                                        ->hidden(fn (Get $get) => $get('is_year_round'))
                                        ->required(fn (Get $get) => ! $get('is_year_round'))
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            static::updateEndDate($get, $set);
                                            static::checkSeasonOverlaps($get, $set);
                                        })
                                        ->dehydrated(false)
                                        ->columnSpan(1),
                                    
                                    // Hidden fields that store the actual MM-DD values
                                    Forms\Components\Hidden::make('start_date')
                                        ->afterStateHydrated(function ($state, Forms\Set $set) {
                                            if ($state && strpos($state, '-') !== false) {
                                                [$month, $day] = explode('-', $state);
                                                $set('start_month', $month);
                                                $set('start_day', $day);
                                            }
                                        }),
                                    Forms\Components\Hidden::make('end_date')
                                        ->afterStateHydrated(function ($state, Forms\Set $set) {
                                            if ($state && strpos($state, '-') !== false) {
                                                [$month, $day] = explode('-', $state);
                                                $set('end_month', $month);
                                                $set('end_day', $day);
                                            }
                                        }),
                                ]),
                            Forms\Components\Placeholder::make('overlap_warning')
                                ->label('')
                                ->content(fn (Forms\Get $get) => static::getOverlapWarning($get))
                                ->visible(fn (Forms\Get $get) => static::hasOverlap($get))
                                ->extraAttributes(['class' => 'text-warning-600 text-sm']),
                            Forms\Components\Repeater::make('time_slots')
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
                                        ->reactive()
                                        ->columnSpan(1),
                                    Forms\Components\TimePicker::make('open_time')
                                        ->label('Opening Time')
                                        ->seconds(false)
                                        ->required()
                                        ->reactive()
                                        ->columnSpan(1),
                                    Forms\Components\TimePicker::make('close_time')
                                        ->label('Closing Time')
                                        ->seconds(false)
                                        ->required()
                                        ->reactive()
                                        ->columnSpan(1),
                                ])
                                ->columns(3)
                                ->defaultItems(0)
                                ->addActionLabel('Add Time Slot')
                                ->reorderable()
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => static::formatTimeSlotLabel($state)),
                        ])
                        ->columns(1)
                        ->defaultItems(1)
                        ->default([
                            [
                                'name' => null,
                                'is_year_round' => true,
                                'start_date' => null,
                                'end_date' => null,
                                'time_slots' => [],
                            ],
                        ])
                        ->addActionLabel('Add Season')
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => 
                            $state['is_year_round'] ?? false
                                ? ($state['name'] ?: 'Year-Round / Ganzjährig')
                                : ($state['name'] ?: 'Season') . 
                                  (isset($state['start_date']) && isset($state['end_date']) 
                                    ? ' (' . static::formatDateForLabel($state['start_date']) . ' - ' . static::formatDateForLabel($state['end_date']) . ')' 
                                    : '')
                        ),
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

                    if (! is_null($record)) {
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
                ->markerColor('#22c55eff')
                ->showFullscreenControl()
                ->showZoomControl()
                ->draggable()
                ->tilesUrl('https://tile.openstreetmap.de/{z}/{x}/{y}.png')
                ->zoom(13)
                ->detectRetina()
                // ->showMyLocationButton()
                ->extraTileControl([])
                ->extraControl([
                    'zoomDelta' => 1,
                    'zoomSnap' => 2,
                ]),
            Forms\Components\TextInput::make('place_id')
                ->label('Google Places ID')
                ->helperText('Enter the Place ID manually or use the finder below')
                ->required()
                ->suffixAction(
                    Forms\Components\Actions\Action::make('clearPlaceId')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn (Set $set) => $set('place_id', null))
                ),
            Forms\Components\ViewField::make('place_id_finder')
                ->label('Place ID Finder')
                ->view('filament.forms.components.place-id-finder')
                ->viewData([
                    'fieldName' => 'place_id',
                ])
                ->dehydrated(false),
        ];
    }

    /**
     * Format date for repeater item label
     */
    protected static function formatDateForLabel(string $date): string
    {
        if (strpos($date, '-') === false) {
            return $date;
        }
        
        [$month, $day] = explode('-', $date);
        return sprintf('%02d.%02d.', (int)$day, (int)$month);
    }

    /**
     * Format time slot label with days and opening hours
     */
    protected static function formatTimeSlotLabel(array $state): ?string
    {
        $days = $state['days'] ?? [];
        $openTime = $state['open_time'] ?? null;
        $closeTime = $state['close_time'] ?? null;

        if (empty($days) || !$openTime || !$closeTime) {
            return 'Time Slot';
        }

        // Day abbreviations (German/English)
        $dayAbbr = [
            'monday' => 'Mo',
            'tuesday' => 'Di',
            'wednesday' => 'Mi',
            'thursday' => 'Do',
            'friday' => 'Fr',
            'saturday' => 'Sa',
            'sunday' => 'So',
        ];

        // Day order
        $dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Sort days by order
        $sortedDays = array_intersect($dayOrder, $days);
        $sortedDays = array_values($sortedDays);

        if (empty($sortedDays)) {
            return 'Time Slot';
        }

        // Format days
        $dayLabels = array_map(fn($day) => $dayAbbr[$day] ?? $day, $sortedDays);
        
        // Try to group consecutive days (e.g., Mo-Fr)
        $formattedDays = static::formatDayRange($sortedDays, $dayAbbr);

        // Format times (remove seconds if present)
        $openFormatted = preg_replace('/:\d{2}$/', '', $openTime);
        $closeFormatted = preg_replace('/:\d{2}$/', '', $closeTime);

        return $formattedDays . ': ' . $openFormatted . '-' . $closeFormatted;
    }

    /**
     * Format day range, grouping consecutive days
     */
    protected static function formatDayRange(array $days, array $dayAbbr): string
    {
        if (empty($days)) {
            return '';
        }

        $dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Find consecutive ranges
        $ranges = [];
        $currentRange = [$days[0]];
        
        for ($i = 1; $i < count($days); $i++) {
            $currentIndex = array_search($days[$i - 1], $dayOrder);
            $nextIndex = array_search($days[$i], $dayOrder);
            
            // Check if consecutive (handling week wrap-around)
            if ($nextIndex === $currentIndex + 1 || ($currentIndex === 6 && $nextIndex === 0)) {
                $currentRange[] = $days[$i];
            } else {
                $ranges[] = $currentRange;
                $currentRange = [$days[$i]];
            }
        }
        $ranges[] = $currentRange;

        // Format ranges
        $formatted = [];
        foreach ($ranges as $range) {
            if (count($range) === 1) {
                $formatted[] = $dayAbbr[$range[0]];
            } elseif (count($range) === 2) {
                $formatted[] = $dayAbbr[$range[0]] . ', ' . $dayAbbr[$range[1]];
            } else {
                $formatted[] = $dayAbbr[$range[0]] . '-' . $dayAbbr[end($range)];
            }
        }

        return implode(', ', $formatted);
    }

    /**
     * Update start_date from start_month and start_day
     */
    protected static function updateStartDate(Get $get, Set $set): void
    {
        $month = $get('start_month');
        $day = $get('start_day');
        
        if ($month && $day) {
            $set('start_date', $month . '-' . $day);
        }
    }

    /**
     * Update end_date from end_month and end_day
     */
    protected static function updateEndDate(Get $get, Set $set): void
    {
        $month = $get('end_month');
        $day = $get('end_day');
        
        if ($month && $day) {
            $set('end_date', $month . '-' . $day);
        }
    }

    /**
     * Check for overlapping season date ranges
     */
    protected static function checkSeasonOverlaps(Get $get, Set $set): void
    {
        // This is called on individual field updates
        // The actual overlap checking is done in hasOverlap() and getOverlapWarning()
    }

    /**
     * Check if there are any overlapping seasons
     */
    protected static function hasOverlap(Get $get): bool
    {
        $seasons = $get('../../manual_opening_hours') ?? [];
        
        if (empty($seasons) || count($seasons) < 2) {
            return false;
        }

        // Filter out year-round and incomplete seasons
        $validSeasons = array_filter($seasons, function ($season) {
            return ! ($season['is_year_round'] ?? false) 
                && ! empty($season['start_date']) 
                && ! empty($season['end_date']);
        });

        if (count($validSeasons) < 2) {
            return false;
        }

        // Check each pair of seasons for overlap
        $validSeasons = array_values($validSeasons);
        for ($i = 0; $i < count($validSeasons); $i++) {
            for ($j = $i + 1; $j < count($validSeasons); $j++) {
                if (static::seasonsOverlap($validSeasons[$i], $validSeasons[$j])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get overlap warning message
     */
    protected static function getOverlapWarning(Get $get): HtmlString
    {
        return new HtmlString('⚠️ <strong>Warning:</strong> Some seasons have overlapping date ranges. This may cause unexpected behavior.');
    }

    /**
     * Check if two seasons overlap
     */
    protected static function seasonsOverlap(array $season1, array $season2): bool
    {
        $start1 = $season1['start_date'];
        $end1 = $season1['end_date'];
        $start2 = $season2['start_date'];
        $end2 = $season2['end_date'];

        // Helper function to check if a date is within a range
        $inRange = function ($date, $start, $end) {
            if ($start <= $end) {
                // Range within same year
                return $date >= $start && $date <= $end;
            } else {
                // Range spans year boundary
                return $date >= $start || $date <= $end;
            }
        };

        // Check if any boundary of season1 is within season2's range
        if ($inRange($start1, $start2, $end2) || $inRange($end1, $start2, $end2)) {
            return true;
        }

        // Check if any boundary of season2 is within season1's range
        if ($inRange($start2, $start1, $end1) || $inRange($end2, $start1, $end1)) {
            return true;
        }

        return false;
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
                Tables\Columns\TextColumn::make('city.name_de')
                    ->label('City')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('city.country.name_de')
                    ->label('Country')
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
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Stadt')
                    ->relationship('city', 'name_de')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('country')
                    ->label('Land')
                    ->query(function ($query, $data) {
                        if (isset($data['value'])) {
                            $query->whereHas('city.country', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }
                    })
                    ->options(function () {
                        return \App\Models\Country::orderBy('name_de')->pluck('name_de', 'id')->toArray();
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('load_dataforseo')
                    ->label('Update')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Update')
                    ->modalDescription('Möchten Sie die DataForSEO Daten für diese Attraktion laden? Der Prozess wird im Hintergrund ausgeführt.')
                    ->modalSubmitActionLabel('Ja, in Queue einreihen')
                    ->action(function (Attraction $record) {
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
                        ->modalHeading('Update für ausgewählte Attraktionen')
                        ->modalDescription('Möchten Sie die Daten für alle ausgewählten Attraktionen aktualisieren? Der Prozess wird im Hintergrund ausgeführt.')
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
            'index' => Pages\ListAttractions::route('/'),
            'create' => Pages\CreateAttraction::route('/create'),
            'edit' => Pages\EditAttraction::route('/{record}/edit'),
        ];
    }
}
