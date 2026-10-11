<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttractionResource\Pages;
use App\Jobs\ProcessDataForSeoOrchestrator;
use App\Models\Attraction;
use App\Models\Country;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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
                    .am-widget{--am-green: #186b29;--am-green-light: #edf5ee;--am-warn: #b8752a;--am-text: #2b2724;--am-text-2: #4b443c;--am-muted: #8c8479;--am-muted-2: #7d7568;--am-closed: #9a9186;--am-sand: #f4f1ec;--am-border: #ddd7ce;--am-border-sand: #e2ddd5;--am-line: #ede9e3;--am-font-heading: Lora, Georgia, serif;--am-font-mono: "IBM Plex Mono", ui-monospace, monospace;box-sizing:border-box;margin:0 0 18px;padding:20px 22px 22px;background:#fff;border:1px solid var(--am-border);border-top:3px solid var(--am-green);border-radius:3px;color:var(--am-text);line-height:1.5}.am-widget *,.am-widget *::before,.am-widget *::after{box-sizing:border-box}.am-widget .am-label{margin-bottom:6px;font-family:var(--am-font-mono);font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--am-green)}.am-widget .am-title{margin:0 0 16px;font-family:var(--am-font-heading);font-size:21px;line-height:1.3;font-weight:700;color:var(--am-text);text-wrap:pretty}.am-widget .am-link{font-weight:600;color:var(--am-green)}.am-widget .am-break{word-break:break-all}.am-contact .am-rows{display:flex;flex-direction:column;margin:0}.am-contact .am-row{display:grid;grid-template-columns:92px 1fr;gap:12px;padding:11px 0;border-top:1px solid var(--am-line)}.am-contact .am-row:last-child{border-bottom:1px solid var(--am-line)}.am-contact .am-row dt{padding-top:2px;font-size:13px;color:var(--am-muted)}.am-contact .am-row dd{margin:0;font-size:16px;line-height:1.5}.am-rating .am-score{display:flex;align-items:center;gap:16px;padding:14px 0;border-top:1px solid var(--am-line);border-bottom:1px solid var(--am-line)}.am-rating .am-number{font-family:var(--am-font-heading);font-size:52px;line-height:1;font-weight:700}.am-rating .am-score-details{display:flex;flex-direction:column;gap:4px}.am-rating .am-stars{font-size:20px;letter-spacing:2px}.am-rating .am-star-full{color:var(--am-warn)}.am-rating .am-star-empty{color:var(--am-border)}.am-rating .am-out-of{font-size:14px;color:var(--am-muted-2)}.am-rating .am-text{margin:12px 0 0;font-size:15px;line-height:1.6;color:var(--am-text-2);text-wrap:pretty}.am-hours{padding-bottom:18px}.am-hours .am-season+.am-season{margin-top:8px}.am-hours .am-season-toggle{display:flex;align-items:center;justify-content:space-between;min-height:44px;padding:0 14px;background:var(--am-sand);border:1px solid var(--am-border-sand);border-radius:3px;font-size:14.5px;font-weight:600;cursor:pointer;list-style:none}.am-hours .am-season-toggle::-webkit-details-marker{display:none}.am-hours .am-season-toggle::after{content:"▾";color:#6d655c;transition:transform .15s ease}.am-hours .am-season-toggle:hover{background:var(--am-line)}.am-hours .am-season:not([open]) .am-season-toggle::after{transform:rotate(-90deg)}.am-hours .am-days{display:flex;flex-direction:column;margin-top:8px}.am-hours .am-day{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 10px;border-bottom:1px solid var(--am-line);font-size:15.5px}.am-hours .am-day.am-today{background:var(--am-green-light)}.am-hours .am-day.am-today .am-day-name{font-weight:700}.am-hours .am-day-name{display:flex;align-items:center;gap:8px;font-weight:600}.am-hours .am-today-tag{font-family:var(--am-font-mono);font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--am-green)}.am-hours .am-time{text-align:right;font-variant-numeric:tabular-nums}.am-hours .am-closed{color:var(--am-closed)}.am-accessibility{padding-bottom:18px}.am-accessibility .am-checks{display:flex;flex-direction:column;margin:0;padding:0;list-style:none}.am-accessibility .am-check{display:flex;align-items:baseline;gap:12px;padding:10px 0;border-top:1px solid var(--am-line);font-size:16px;line-height:1.5}.am-accessibility .am-check:last-child{border-bottom:1px solid var(--am-line)}.am-accessibility .am-mark{font-weight:700}.am-accessibility .am-ok .am-mark{color:var(--am-green)}.am-accessibility .am-warn .am-mark{color:var(--am-warn)}
                    </style>
                ')),
            Forms\Components\Tabs::make('Languages')
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('English')
                        ->label(fn (Get $get) => '🇬🇧 EN · '.($get('en_name') ?? 'New Attraction'))
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
                        ->label(fn (Get $get) => '🇩🇪 DE · '.($get('name') ?? 'Neue Attraktion'))
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
            Forms\Components\Section::make('Location & Address')
                ->description('Geographical location and address information')
                ->collapsible()
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('search_address')
                                    ->label('Search Address based on Attraction Name')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->color('primary')
                                    ->action(function (Set $set, Get $get) {
                                        $germanName = $get('name');
                                        $searchQuery = $get('en_name')
                                            ?: ($germanName !== 'Neue Attraktion' ? $germanName : null);

                                        if (empty($searchQuery)) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Please enter a name or address.')
                                                ->warning()
                                                ->send();

                                            return;
                                        }

                                        // Use Nominatim (OpenStreetMap) for geocoding
                                        try {
                                            $response = Http::withUserAgent('TravelWorker Attraction Finder')
                                                ->timeout(10)
                                                ->get('https://nominatim.openstreetmap.org/search', [
                                                    'q' => $searchQuery,
                                                    'format' => 'json',
                                                    'limit' => 1,
                                                    'addressdetails' => 1,
                                                    'accept-language' => 'en',
                                                ]);
                                        } catch (ConnectionException) {
                                            $response = null;
                                        }

                                        if ($response?->successful()) {
                                            $results = $response->json();

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

                                                Notification::make()
                                                    ->title('Address Found')
                                                    ->body('Address data and coordinates have been successfully updated.')
                                                    ->success()
                                                    ->send();
                                            } else {
                                                Notification::make()
                                                    ->title('No Results')
                                                    ->body('No results were found for this search.')
                                                    ->warning()
                                                    ->send();
                                            }
                                        } else {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Address search failed. Please try again.')
                                                ->danger()
                                                ->send();
                                        }
                                    }),
                            ])
                                ->columnSpan(2),
                            Forms\Components\Select::make('city_id')
                                ->label('City')
                                ->relationship('city', 'name_de')
                                ->searchable(['name_de', 'name_en'])
                                ->preload()
                                ->required()
                                ->columnSpan(2)
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
                            Forms\Components\TextInput::make('street')
                                ->required()
                                ->label('Street')
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('zip')
                                ->required()
                                ->label('ZIP')
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('latitude')
                                ->label('Latitude')
                                ->required()
                                ->live()
                                ->numeric()
                                ->columnSpan(1)
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
                                ->columnSpan(1)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $lat = $get('latitude');
                                    if ($state && $lat) {
                                        $set('map', ['lat' => (float) $lat, 'lng' => (float) $state]);
                                    }
                                }),
                            Map::make('map')
                                ->label('Map')
                                ->columnSpan(2)
                                ->afterStateUpdated(function (Set $set, ?array $state): void {
                                    $set('latitude', $state['lat']);
                                    $set('longitude', $state['lng']);
                                })
                                ->afterStateHydrated(function ($state, $record, Set $set): void {
                                    if (! is_null($record)) {
                                        $set('map', ['lat' => $record->latitude, 'lng' => $record->longitude]);
                                    } elseif (is_array($state) && isset($state['lat']) && isset($state['lng']) && $state['lat'] !== 0 && $state['lng'] !== 0) {
                                        $set('map', ['lat' => $state['lat'], 'lng' => $state['lng']]);
                                    } else {
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
                                ->extraTileControl([])
                                ->extraStyles(['min-height: 60vh'])
                                ->extraControl([
                                    'zoomDelta' => 1,
                                    'zoomSnap' => 2,
                                    'scrollWheelZoom' => false,
                                ]),
                        ]),
                ]),
            Forms\Components\Section::make('Contact & Rating')
                ->description('Contact information and rating details')
                ->collapsible()
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('rating_value')
                                ->label('Rating Value')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(5)
                                ->step(0.1)
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('rating_votes_count')
                                ->label('Rating Count')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step(1)
                                ->columnSpan(1),
                        ]),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Textarea::make('rating_text')
                                ->label('Rating text (DE)')
                                ->helperText('Kurze Begründung unter der Bewertung im Widget')
                                ->rows(3)
                                ->columnSpan(1),
                            Forms\Components\Textarea::make('en_rating_text')
                                ->label('Rating text (EN)')
                                ->helperText('Short justification below the rating in the widget')
                                ->rows(3)
                                ->columnSpan(1),
                        ]),
                ]),
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
                                            $seasonCount = count(array_filter($allSeasons, function ($season) {
                                                return ! empty($season); // Filter out empty entries
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
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            static::updateStartDate($get, $set);
                                            static::checkSeasonOverlaps($get, $set);
                                        })
                                        ->dehydrated(false)
                                        ->columnSpan(1),
                                    Forms\Components\Select::make('start_day')
                                        ->label('Start Day')
                                        ->options(fn () => array_combine(
                                            array_map(fn ($i) => sprintf('%02d', $i), range(1, 31)),
                                            range(1, 31)
                                        ))
                                        ->hidden(fn (Get $get) => $get('is_year_round'))
                                        ->required(fn (Get $get) => ! $get('is_year_round'))
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
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
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            static::updateEndDate($get, $set);
                                            static::checkSeasonOverlaps($get, $set);
                                        })
                                        ->dehydrated(false)
                                        ->columnSpan(1),
                                    Forms\Components\Select::make('end_day')
                                        ->label('End Day')
                                        ->options(fn () => array_combine(
                                            array_map(fn ($i) => sprintf('%02d', $i), range(1, 31)),
                                            range(1, 31)
                                        ))
                                        ->hidden(fn (Get $get) => $get('is_year_round'))
                                        ->required(fn (Get $get) => ! $get('is_year_round'))
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            static::updateEndDate($get, $set);
                                            static::checkSeasonOverlaps($get, $set);
                                        })
                                        ->dehydrated(false)
                                        ->columnSpan(1),

                                    // Hidden fields that store the actual MM-DD values
                                    Forms\Components\Hidden::make('start_date')
                                        ->afterStateHydrated(function ($state, Set $set) {
                                            if ($state && strpos($state, '-') !== false) {
                                                [$month, $day] = explode('-', $state);
                                                $set('start_month', $month);
                                                $set('start_day', $day);
                                            }
                                        }),
                                    Forms\Components\Hidden::make('end_date')
                                        ->afterStateHydrated(function ($state, Set $set) {
                                            if ($state && strpos($state, '-') !== false) {
                                                [$month, $day] = explode('-', $state);
                                                $set('end_month', $month);
                                                $set('end_day', $day);
                                            }
                                        }),
                                ]),
                            Forms\Components\Placeholder::make('overlap_warning')
                                ->label('')
                                ->content(fn (Get $get) => static::getOverlapWarning($get))
                                ->visible(fn (Get $get) => static::hasOverlap($get))
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
                        ->itemLabel(fn (array $state): ?string => $state['is_year_round'] ?? false
                                ? ($state['name'] ?: 'Year-Round / Ganzjährig')
                                : ($state['name'] ?: 'Season').
                                  (isset($state['start_date']) && isset($state['end_date'])
                                    ? ' ('.static::formatDateForLabel($state['start_date']).' - '.static::formatDateForLabel($state['end_date']).')'
                                    : '')
                        ),
                ]),
            Forms\Components\Section::make('Google Places')
                ->description('Google Places API integration')
                ->collapsible()
                ->collapsed()
                ->schema([
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
                ]),
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

        return sprintf('%02d.%02d.', (int) $day, (int) $month);
    }

    /**
     * Format time slot label with days and opening hours
     */
    protected static function formatTimeSlotLabel(array $state): ?string
    {
        $days = $state['days'] ?? [];
        $openTime = $state['open_time'] ?? null;
        $closeTime = $state['close_time'] ?? null;

        if (empty($days) || ! $openTime || ! $closeTime) {
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
        $dayLabels = array_map(fn ($day) => $dayAbbr[$day] ?? $day, $sortedDays);

        // Try to group consecutive days (e.g., Mo-Fr)
        $formattedDays = static::formatDayRange($sortedDays, $dayAbbr);

        // Format times (remove seconds if present)
        $openFormatted = preg_replace('/:\d{2}$/', '', $openTime);
        $closeFormatted = preg_replace('/:\d{2}$/', '', $closeTime);

        return $formattedDays.': '.$openFormatted.'-'.$closeFormatted;
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
                $formatted[] = $dayAbbr[$range[0]].', '.$dayAbbr[$range[1]];
            } else {
                $formatted[] = $dayAbbr[$range[0]].'-'.$dayAbbr[end($range)];
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
            $set('start_date', $month.'-'.$day);
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
            $set('end_date', $month.'-'.$day);
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
                        return Country::orderBy('name_de')->pluck('name_de', 'id')->toArray();
                    })
                    ->searchable()
                    ->preload(),
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
                    Tables\Actions\BulkAction::make('bulk_load_dataforseo')
                        ->label('Update')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Update für ausgewählte Attraktionen')
                        ->modalDescription('Möchten Sie die Daten für alle ausgewählten Attraktionen aktualisieren? Der Prozess wird im Hintergrund ausgeführt.')
                        ->modalSubmitActionLabel('Ja, alle in Queue einreihen')
                        ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'admin', 'editor']) ?? false)
                        ->action(function (Collection $records) {
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

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view attractions') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create attractions') ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Super Admin, Admin und Editor können alles bearbeiten
        if ($user->hasAnyRole(['super_admin', 'admin', 'editor'])) {
            return $user->can('edit attractions');
        }

        // Attractions Author kann nur eigene Attractions bearbeiten
        if ($user->hasRole('attractions-author')) {
            // Prüfe ob User die Berechtigung "edit own attractions" hat
            if (! $user->can('edit own attractions')) {
                return false;
            }

            // TODO: Wenn user_id Feld hinzugefügt wird, hier Prüfung auf eigene Attractions einbauen
            // Aktuell können alle Attractions-Autoren alle Attractions bearbeiten,
            // bis das user_id Feld implementiert ist
            return true;
        }

        return false;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Editor kann nicht löschen
        if ($user->hasRole('editor')) {
            return false;
        }

        // Super Admin und Admin können alles löschen
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $user->can('delete attractions');
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view attractions') ?? false;
    }
}
