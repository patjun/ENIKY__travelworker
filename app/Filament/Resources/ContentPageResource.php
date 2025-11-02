<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentPageResource\Pages;
use App\Models\ContentPage;
use App\Models\LocationBlock;
use App\Models\RelatedLinksBlock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ContentPageResource extends Resource
{
    protected static ?string $model = ContentPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Tabs::make('Languages')
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('German')
                        ->label(fn (Get $get) => ($get('title_de') ?? 'Neue Content Page').' - DE')
                        ->schema([
                            Forms\Components\Section::make('Grunddaten')
                                ->schema([
                                    Forms\Components\TextInput::make('title_de')
                                        ->label('Titel')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Forms\Set $set, ?string $state, ?string $old) {
                                            if (($old ?? '') === '') {
                                                $set('slug_de', Str::slug($state));
                                            }
                                        }),
                                    Forms\Components\TextInput::make('slug_de')
                                        ->label('Slug')
                                        ->required()
                                        ->unique(ignoreRecord: true),
                                    Forms\Components\RichEditor::make('intro_de')
                                        ->label('Intro Text')
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('meta_description_de')
                                        ->label('Meta Description')
                                        ->maxLength(160)
                                        ->helperText('Max. 160 Zeichen für SEO'),
                                ]),

                            Forms\Components\Section::make('Content Blocks')
                                ->description('Füge Locations, Links und andere Inhalte hinzu. Die Reihenfolge kann beliebig angepasst werden.')
                                ->schema([
                                    Forms\Components\Repeater::make('content_blocks_data')
                                        ->label('Inhalt')
                                        ->schema([
                                            Forms\Components\Select::make('block_type')
                                                ->label('Block Typ')
                                                ->options([
                                                    'location' => 'Location',
                                                    'related_links' => 'Weitere Links',
                                                ])
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('block_data', null)),

                                            // Location Block Fields
                                            Forms\Components\Select::make('location_id')
                                                ->label('Location')
                                                ->options(\App\Models\Location::all()->pluck('name', 'id'))
                                                ->searchable()
                                                ->required()
                                                ->live()
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'location'),
                                            Forms\Components\RichEditor::make('custom_intro_de')
                                                ->label('Custom Intro (DE)')
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'location'),
                                            Forms\Components\RichEditor::make('custom_intro_en')
                                                ->label('Custom Intro (EN)')
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'location'),

                                            // Related Links Block Fields
                                            Forms\Components\TextInput::make('title_de')
                                                ->label('Block Titel (DE)')
                                                ->default('Das könnte Dich auch interessieren')
                                                ->live()
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'related_links'),
                                            Forms\Components\TextInput::make('title_en')
                                                ->label('Block Titel (EN)')
                                                ->default('You might also be interested in')
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'related_links'),
                                            Forms\Components\Repeater::make('links')
                                                ->label('Links')
                                                ->schema([
                                                    Forms\Components\TextInput::make('title_de')
                                                        ->label('Link Titel (DE)')
                                                        ->required(),
                                                    Forms\Components\TextInput::make('title_en')
                                                        ->label('Link Titel (EN)'),
                                                    Forms\Components\TextInput::make('url')
                                                        ->label('URL')
                                                        ->url()
                                                        ->required(),
                                                ])
                                                ->columns(3)
                                                ->collapsible()
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'related_links'),
                                        ])
                                        ->reorderable()
                                        ->collapsible()
                                        ->collapsed()
                                        ->itemLabel(function (array $state): ?string {
                                            if (($state['block_type'] ?? '') === 'location') {
                                                return 'Location: ' . (\App\Models\Location::find($state['location_id'])?->name ?? 'Unbekannt');
                                            }
                                            if (($state['block_type'] ?? '') === 'related_links') {
                                                return 'Weitere Links: ' . ($state['title_de'] ?? 'Unbenannt');
                                            }
                                            return 'Neuer Block';
                                        })
                                        ->defaultItems(0)
                                        ->addActionLabel('Block hinzufügen'),
                                ]),

                            Forms\Components\Section::make('HTML Preview')
                                ->schema([
                                    Forms\Components\Placeholder::make('generated_html_de_preview')
                                        ->label('Generierter HTML Code')
                                        ->content(function (?ContentPage $record): HtmlString {
                                            if (!$record || !$record->generated_html_de) {
                                                return new HtmlString('<p>HTML wird nach dem Speichern generiert...</p>');
                                            }

                                            return new HtmlString('<div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #f9fafb; max-height: 400px; overflow-y: auto;"><pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word; font-size: 12px;">' . htmlspecialchars($record->generated_html_de) . '</pre></div>');
                                        }),
                                ])
                                ->collapsible()
                                ->collapsed(),
                        ]),

                    Forms\Components\Tabs\Tab::make('English')
                        ->label(fn (Get $get) => ($get('title_en') ?? 'New Content Page').' - EN')
                        ->schema([
                            Forms\Components\Section::make('Basic Info')
                                ->schema([
                                    Forms\Components\TextInput::make('title_en')
                                        ->label('Title')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Forms\Set $set, ?string $state, ?string $old) {
                                            if (($old ?? '') === '') {
                                                $set('slug_en', Str::slug($state));
                                            }
                                        }),
                                    Forms\Components\TextInput::make('slug_en')
                                        ->label('Slug')
                                        ->unique(ignoreRecord: true),
                                    Forms\Components\RichEditor::make('intro_en')
                                        ->label('Intro Text')
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('meta_description_en')
                                        ->label('Meta Description')
                                        ->maxLength(160)
                                        ->helperText('Max. 160 characters for SEO'),
                                ]),

                            Forms\Components\Section::make('HTML Preview')
                                ->schema([
                                    Forms\Components\Placeholder::make('generated_html_en_preview')
                                        ->label('Generated HTML Code')
                                        ->content(function (?ContentPage $record): HtmlString {
                                            if (!$record || !$record->generated_html_en) {
                                                return new HtmlString('<p>HTML will be generated after saving...</p>');
                                            }

                                            return new HtmlString('<div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #f9fafb; max-height: 400px; overflow-y: auto;"><pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word; font-size: 12px;">' . htmlspecialchars($record->generated_html_en) . '</pre></div>');
                                        }),
                                ])
                                ->collapsible()
                                ->collapsed(),
                        ]),

                    Forms\Components\Tabs\Tab::make('Settings')
                        ->label('Einstellungen')
                        ->schema([
                            Forms\Components\Section::make('Publishing')
                                ->schema([
                                    Forms\Components\Select::make('status')
                                        ->label('Status')
                                        ->options([
                                            'draft' => 'Entwurf',
                                            'published' => 'Veröffentlicht',
                                        ])
                                        ->default('draft')
                                        ->required(),
                                    Forms\Components\DateTimePicker::make('published_at')
                                        ->label('Veröffentlichungsdatum'),
                                ]),
                        ]),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title_de')
                    ->label('Titel (DE)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title_en')
                    ->label('Title (EN)')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('slug_de')
                    ->label('Slug (DE)')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('content_blocks_count')
                    ->label('Blöcke')
                    ->counts('contentBlocks')
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Veröffentlicht')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Entwurf',
                        'published' => 'Veröffentlicht',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListContentPages::route('/'),
            'create' => Pages\CreateContentPage::route('/create'),
            'edit' => Pages\EditContentPage::route('/{record}/edit'),
        ];
    }
}
