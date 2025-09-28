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
                                                ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : null),
                                            Forms\Components\Textarea::make('opening_hours_html')
                                                ->label('Opening Hours Widget (DE)')
                                                ->disabled()
                                                ->rows(8),
                                            Forms\Components\Textarea::make('structured_data')
                                                ->label('Structured Data (DE)')
                                                ->disabled()
                                                ->rows(15),
                                            Forms\Components\Textarea::make('contact_info_html')
                                                ->label('Contact Info Widget (DE)')
                                                ->disabled()
                                                ->rows(8),
                                            Forms\Components\Textarea::make('rating_html')
                                                ->label('Rating Widget (DE)')
                                                ->disabled()
                                                ->rows(6),
                                            Forms\Components\Textarea::make('accessibility_html')
                                                ->label('Accessibility Widget (DE)')
                                                ->disabled()
                                                ->rows(6),
                                        ]),
                                    Forms\Components\Section::make('Widgets')
                                        ->columnSpan(1)
                                        ->schema([
                                            Forms\Components\Placeholder::make('widget_styles')
                                                ->label('')
                                                ->content(new HtmlString('
                                                    <style>
                                                    .contact-info-widget {
                                                      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                                      color: white;
                                                      padding: 25px;
                                                      border-radius: 12px;
                                                      font-family: Arial, sans-serif;
                                                      width: 400px;
                                                      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                                                      margin-bottom: 20px;
                                                    }
                                                    .contact-header {
                                                      text-align: center;
                                                      margin-bottom: 20px;
                                                      border-bottom: 2px solid rgba(255,255,255,0.3);
                                                      padding-bottom: 15px;
                                                    }
                                                    .contact-name {
                                                      margin: 0;
                                                      font-size: 24px;
                                                      font-weight: bold;
                                                      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                                    }
                                                    .contact-details {
                                                      display: flex;
                                                      flex-direction: column;
                                                      gap: 15px;
                                                    }
                                                    .contact-item {
                                                      display: flex;
                                                      align-items: flex-start;
                                                      gap: 12px;
                                                      padding: 10px;
                                                      background: rgba(255,255,255,0.1);
                                                      border-radius: 8px;
                                                      backdrop-filter: blur(10px);
                                                    }
                                                    .contact-icon {
                                                      font-size: 20px;
                                                      width: 24px;
                                                      text-align: center;
                                                      flex-shrink: 0;
                                                    }
                                                    .contact-info {
                                                      flex: 1;
                                                      line-height: 1.4;
                                                    }
                                                    .address-line {
                                                      margin-bottom: 2px;
                                                    }
                                                    .contact-link {
                                                      color: white;
                                                      text-decoration: none;
                                                      border-bottom: 1px solid rgba(255,255,255,0.5);
                                                      transition: border-bottom-color 0.3s ease;
                                                    }
                                                    .contact-link:hover {
                                                      border-bottom-color: white;
                                                    }
                                                    .opening-hours-widget {
                                                      background-color: #333;
                                                      color: white;
                                                      padding: 20px;
                                                      font-family: Arial, sans-serif;
                                                      width: 400px;
                                                      text-transform: uppercase;
                                                    }
                                                    .opening-hours-header {
                                                      text-align: center;
                                                      border-top: 3px solid white;
                                                      border-bottom: 3px solid white;
                                                      padding: 10px 0;
                                                      margin-bottom: 20px;
                                                    }
                                                    .opening-hours-title {
                                                      margin: 0;
                                                      font-size: 24px;
                                                      font-weight: bold;
                                                      letter-spacing: 2px;
                                                    }
                                                    .opening-hours-day {
                                                      display: flex;
                                                      justify-content: space-between;
                                                      margin-bottom: 8px;
                                                      font-size: 18px;
                                                    }
                                                    .opening-hours-day-name {
                                                      font-weight: bold;
                                                    }
                                                    .rating-widget {
                                                      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                                                      color: white;
                                                      padding: 20px;
                                                      border-radius: 12px;
                                                      font-family: Arial, sans-serif;
                                                      width: 400px;
                                                      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                                                      margin-bottom: 20px;
                                                    }
                                                    .rating-header {
                                                      text-align: center;
                                                      margin-bottom: 15px;
                                                      border-bottom: 2px solid rgba(255,255,255,0.3);
                                                      padding-bottom: 10px;
                                                    }
                                                    .rating-title {
                                                      margin: 0;
                                                      font-size: 20px;
                                                      font-weight: bold;
                                                      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                                    }
                                                    .rating-content {
                                                      display: flex;
                                                      justify-content: space-between;
                                                      align-items: center;
                                                    }
                                                    .rating-main {
                                                      display: flex;
                                                      align-items: center;
                                                      gap: 15px;
                                                    }
                                                    .rating-score {
                                                      font-size: 48px;
                                                      font-weight: bold;
                                                      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                                    }
                                                    .rating-details {
                                                      display: flex;
                                                      flex-direction: column;
                                                      gap: 5px;
                                                    }
                                                    .rating-stars {
                                                      display: flex;
                                                      gap: 2px;
                                                    }
                                                    .star {
                                                      font-size: 20px;
                                                    }
                                                    .star-full {
                                                      color: #FFD700;
                                                      text-shadow: 0 1px 2px rgba(0,0,0,0.5);
                                                    }
                                                    .star-half {
                                                      color: #FFD700;
                                                      text-shadow: 0 1px 2px rgba(0,0,0,0.5);
                                                    }
                                                    .star-empty {
                                                      color: rgba(255,255,255,0.4);
                                                    }
                                                    .rating-text {
                                                      font-size: 14px;
                                                      opacity: 0.9;
                                                    }
                                                    .rating-reviews {
                                                      text-align: right;
                                                      display: flex;
                                                      flex-direction: column;
                                                      align-items: flex-end;
                                                    }
                                                    .reviews-count {
                                                      font-size: 24px;
                                                      font-weight: bold;
                                                      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                                    }
                                                    .reviews-label {
                                                      font-size: 12px;
                                                      opacity: 0.9;
                                                    }
                                                    .accessibility-widget {
                                                      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                                                      color: white;
                                                      padding: 20px;
                                                      border-radius: 12px;
                                                      font-family: Arial, sans-serif;
                                                      width: 400px;
                                                      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                                                      margin-bottom: 20px;
                                                    }
                                                    .accessibility-header {
                                                      text-align: center;
                                                      margin-bottom: 15px;
                                                      border-bottom: 2px solid rgba(255,255,255,0.3);
                                                      padding-bottom: 10px;
                                                    }
                                                    .accessibility-title {
                                                      margin: 0;
                                                      font-size: 20px;
                                                      font-weight: bold;
                                                      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                                    }
                                                    .accessibility-features {
                                                      display: flex;
                                                      flex-direction: column;
                                                      gap: 10px;
                                                    }
                                                    .accessibility-item {
                                                      display: flex;
                                                      align-items: center;
                                                      gap: 12px;
                                                      padding: 8px 12px;
                                                      border-radius: 8px;
                                                      background: rgba(255,255,255,0.1);
                                                      backdrop-filter: blur(10px);
                                                    }
                                                    .accessibility-status {
                                                      font-size: 14px;
                                                      width: 20px;
                                                      text-align: center;
                                                      flex-shrink: 0;
                                                      color: #4CAF50;
                                                      font-weight: 500;
                                                    }
                                                    .accessibility-label {
                                                      flex: 1;
                                                      font-size: 14px;
                                                      font-weight: 500;
                                                    }
                                                    .accessibility-available {
                                                      border-left: 4px solid #4CAF50;
                                                    }
                                                    .accessibility-unavailable {
                                                      border-left: 4px solid #f44336;
                                                      opacity: 0.8;
                                                    }
                                                    </style>
                                                ')),
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
                                            Forms\Components\TextInput::make('en_city')
                                                ->label('City (EN)'),
                                            Forms\Components\TextInput::make('en_country')
                                                ->label('Country (EN)'),
                                            Forms\Components\TextInput::make('en_phone')
                                                ->label('Phone (EN)'),
                                            Forms\Components\TextInput::make('en_website')
                                                ->label('Website (EN)'),
                                            Forms\Components\Textarea::make('en_description')
                                                ->label('Description (EN)'),
                                            Forms\Components\TextInput::make('en_category')
                                                ->label('Category (EN)'),
                                            Forms\Components\Textarea::make('en_opening_hours')
                                                ->label('Opening Hours (EN)')
                                                ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : null),
                                            Forms\Components\Textarea::make('en_attributes')
                                                ->label('Attributes (EN)')
                                                ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : null),
                                            Forms\Components\TextInput::make('en_main_image_url')
                                                ->label('Main Image URL (EN)'),
                                            Forms\Components\TextInput::make('en_price_level')
                                                ->label('Price Level (EN)'),
                                            Forms\Components\Textarea::make('en_additional_categories')
                                                ->label('Additional Categories (EN)')
                                                ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : null),
                                            Forms\Components\Textarea::make('en_opening_hours_html')
                                                ->label('Opening Hours Widget (EN)')
                                                ->disabled()
                                                ->rows(8),
                                            Forms\Components\Textarea::make('en_structured_data')
                                                ->label('Structured Data (EN)')
                                                ->disabled()
                                                ->rows(15),
                                            Forms\Components\Textarea::make('en_contact_info_html')
                                                ->label('Contact Info Widget (EN)')
                                                ->disabled()
                                                ->rows(8),
                                            Forms\Components\Textarea::make('en_rating_html')
                                                ->label('Rating Widget (EN)')
                                                ->disabled()
                                                ->rows(6),
                                            Forms\Components\Textarea::make('en_accessibility_html')
                                                ->label('Accessibility Widget (EN)')
                                                ->disabled()
                                                ->rows(6),
                                        ]),
                                    Forms\Components\Section::make('Widgets')
                                        ->columnSpan(1)
                                        ->schema([
                                            Forms\Components\Placeholder::make('en_widget_styles')
                                                ->label('')
                                                ->content(new HtmlString('
                                                    <style>
                                                    .contact-info-widget {
                                                      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                                      color: white;
                                                      padding: 25px;
                                                      border-radius: 12px;
                                                      font-family: Arial, sans-serif;
                                                      width: 400px;
                                                      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                                                      margin-bottom: 20px;
                                                    }
                                                    .contact-header {
                                                      text-align: center;
                                                      margin-bottom: 20px;
                                                      border-bottom: 2px solid rgba(255,255,255,0.3);
                                                      padding-bottom: 15px;
                                                    }
                                                    .contact-name {
                                                      margin: 0;
                                                      font-size: 24px;
                                                      font-weight: bold;
                                                      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                                    }
                                                    .contact-details {
                                                      display: flex;
                                                      flex-direction: column;
                                                      gap: 15px;
                                                    }
                                                    .contact-item {
                                                      display: flex;
                                                      align-items: flex-start;
                                                      gap: 12px;
                                                      padding: 10px;
                                                      background: rgba(255,255,255,0.1);
                                                      border-radius: 8px;
                                                      backdrop-filter: blur(10px);
                                                    }
                                                    .contact-icon {
                                                      font-size: 20px;
                                                      width: 24px;
                                                      text-align: center;
                                                      flex-shrink: 0;
                                                    }
                                                    .contact-info {
                                                      flex: 1;
                                                      line-height: 1.4;
                                                    }
                                                    .address-line {
                                                      margin-bottom: 2px;
                                                    }
                                                    .contact-link {
                                                      color: white;
                                                      text-decoration: none;
                                                      border-bottom: 1px solid rgba(255,255,255,0.5);
                                                      transition: border-bottom-color 0.3s ease;
                                                    }
                                                    .contact-link:hover {
                                                      border-bottom-color: white;
                                                    }
                                                    .opening-hours-widget {
                                                      background-color: #333;
                                                      color: white;
                                                      padding: 20px;
                                                      font-family: Arial, sans-serif;
                                                      width: 400px;
                                                      text-transform: uppercase;
                                                    }
                                                    .opening-hours-header {
                                                      text-align: center;
                                                      border-top: 3px solid white;
                                                      border-bottom: 3px solid white;
                                                      padding: 10px 0;
                                                      margin-bottom: 20px;
                                                    }
                                                    .opening-hours-title {
                                                      margin: 0;
                                                      font-size: 24px;
                                                      font-weight: bold;
                                                      letter-spacing: 2px;
                                                    }
                                                    .opening-hours-day {
                                                      display: flex;
                                                      justify-content: space-between;
                                                      margin-bottom: 8px;
                                                      font-size: 18px;
                                                    }
                                                    .opening-hours-day-name {
                                                      font-weight: bold;
                                                    }
                                                    .rating-widget {
                                                      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                                                      color: white;
                                                      padding: 20px;
                                                      border-radius: 12px;
                                                      font-family: Arial, sans-serif;
                                                      width: 400px;
                                                      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                                                      margin-bottom: 20px;
                                                    }
                                                    .rating-header {
                                                      text-align: center;
                                                      margin-bottom: 15px;
                                                      border-bottom: 2px solid rgba(255,255,255,0.3);
                                                      padding-bottom: 10px;
                                                    }
                                                    .rating-title {
                                                      margin: 0;
                                                      font-size: 20px;
                                                      font-weight: bold;
                                                      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                                    }
                                                    .rating-content {
                                                      display: flex;
                                                      justify-content: space-between;
                                                      align-items: center;
                                                    }
                                                    .rating-main {
                                                      display: flex;
                                                      align-items: center;
                                                      gap: 15px;
                                                    }
                                                    .rating-score {
                                                      font-size: 48px;
                                                      font-weight: bold;
                                                      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                                    }
                                                    .rating-details {
                                                      display: flex;
                                                      flex-direction: column;
                                                      gap: 5px;
                                                    }
                                                    .rating-stars {
                                                      display: flex;
                                                      gap: 2px;
                                                    }
                                                    .star {
                                                      font-size: 20px;
                                                    }
                                                    .star-full {
                                                      color: #FFD700;
                                                      text-shadow: 0 1px 2px rgba(0,0,0,0.5);
                                                    }
                                                    .star-half {
                                                      color: #FFD700;
                                                      text-shadow: 0 1px 2px rgba(0,0,0,0.5);
                                                    }
                                                    .star-empty {
                                                      color: rgba(255,255,255,0.4);
                                                    }
                                                    .rating-text {
                                                      font-size: 14px;
                                                      opacity: 0.9;
                                                    }
                                                    .rating-reviews {
                                                      text-align: right;
                                                      display: flex;
                                                      flex-direction: column;
                                                      align-items: flex-end;
                                                    }
                                                    .reviews-count {
                                                      font-size: 24px;
                                                      font-weight: bold;
                                                      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                                    }
                                                    .reviews-label {
                                                      font-size: 12px;
                                                      opacity: 0.9;
                                                    }
                                                    .accessibility-widget {
                                                      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                                                      color: white;
                                                      padding: 20px;
                                                      border-radius: 12px;
                                                      font-family: Arial, sans-serif;
                                                      width: 400px;
                                                      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                                                      margin-bottom: 20px;
                                                    }
                                                    .accessibility-header {
                                                      text-align: center;
                                                      margin-bottom: 15px;
                                                      border-bottom: 2px solid rgba(255,255,255,0.3);
                                                      padding-bottom: 10px;
                                                    }
                                                    .accessibility-title {
                                                      margin: 0;
                                                      font-size: 20px;
                                                      font-weight: bold;
                                                      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                                                    }
                                                    .accessibility-features {
                                                      display: flex;
                                                      flex-direction: column;
                                                      gap: 10px;
                                                    }
                                                    .accessibility-item {
                                                      display: flex;
                                                      align-items: center;
                                                      gap: 12px;
                                                      padding: 8px 12px;
                                                      border-radius: 8px;
                                                      background: rgba(255,255,255,0.1);
                                                      backdrop-filter: blur(10px);
                                                    }
                                                    .accessibility-status {
                                                      font-size: 14px;
                                                      width: 20px;
                                                      text-align: center;
                                                      flex-shrink: 0;
                                                      color: #4CAF50;
                                                      font-weight: 500;
                                                    }
                                                    .accessibility-label {
                                                      flex: 1;
                                                      font-size: 14px;
                                                      font-weight: 500;
                                                    }
                                                    .accessibility-available {
                                                      border-left: 4px solid #4CAF50;
                                                    }
                                                    .accessibility-unavailable {
                                                      border-left: 4px solid #f44336;
                                                      opacity: 0.8;
                                                    }
                                                    </style>
                                                ')),
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
        ];
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
                    ->modalHeading('Update')
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
