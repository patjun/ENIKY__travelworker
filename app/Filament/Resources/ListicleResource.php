<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListicleResource\Pages;
use App\Models\AttractionBlock;
use App\Models\Listicle;
use App\Models\RelatedLinksBlock;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ListicleResource extends Resource
{
    protected static ?string $model = Listicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content Management';

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
                    Forms\Components\Tabs\Tab::make('English')
                        ->label(fn (Get $get) => ($get('title_en') ?? 'New Listicle').' - EN')
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
                                        ->disableToolbarButtons(['attachFiles', 'codeBlock'])
                                        ->columnSpanFull(),
                                    FileUpload::make('image_en')
                                        ->label('Featured Image')
                                        ->image()
                                        ->imageEditor()
                                        ->imageCropAspectRatio('16:9')
                                        ->imageResizeTargetWidth('1920')
                                        ->imageResizeTargetHeight('1080')
                                        ->imageResizeMode('cover')
                                        ->disk('public')
                                        ->directory('listicle-images')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                        ->rules(['dimensions:min_width=1200,min_height=675'])
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('meta_description_en')
                                        ->label('Meta Description')
                                        ->maxLength(160)
                                        ->helperText('Max. 160 characters for SEO'),
                                ]),

                            Forms\Components\Section::make('Content Blocks')
                                ->description('Add locations, links and other content. The order can be customized.')
                                ->schema([
                                    Forms\Components\Repeater::make('content_blocks_data_en')
                                        ->label('Content (EN)')
                                        ->schema([
                                            Forms\Components\Select::make('block_type')
                                                ->label('Block Type')
                                                ->options([
                                                    'location' => 'Location',
                                                    'related_links' => 'Related Links',
                                                ])
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('block_data', null)),

                                            // Attraction Block Fields
                                            Forms\Components\Select::make('attraction_id')
                                                ->label('Attraction')
                                                ->options(\App\Models\Attraction::all()->pluck('name', 'id'))
                                                ->searchable()
                                                ->required()
                                                ->live()
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'location'),
                                            Forms\Components\RichEditor::make('custom_intro')
                                                ->label('Custom Intro')
                                                ->disableToolbarButtons(['attachFiles', 'codeBlock'])
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'location'),

                                            // Related Links Block Fields
                                            Forms\Components\TextInput::make('title')
                                                ->label('Block Title')
                                                ->default('You might also be interested in')
                                                ->live()
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'related_links'),
                                            Forms\Components\Repeater::make('links')
                                                ->label('Links')
                                                ->schema([
                                                    Forms\Components\TextInput::make('title')
                                                        ->label('Link Title')
                                                        ->required(),
                                                    Forms\Components\TextInput::make('url')
                                                        ->label('URL')
                                                        ->url()
                                                        ->required(),
                                                ])
                                                ->columns(2)
                                                ->collapsible()
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'related_links'),
                                        ])
                                        ->reorderable()
                                        ->collapsible()
                                        ->collapsed()
                                        ->itemLabel(function (array $state): ?string {
                                            if (($state['block_type'] ?? '') === 'location') {
                                                return 'Attraction: ' . (\App\Models\Attraction::find($state['attraction_id'])?->name ?? 'Unknown');
                                            }
                                            if (($state['block_type'] ?? '') === 'related_links') {
                                                return 'Related Links: ' . ($state['title'] ?? 'Untitled');
                                            }
                                            return 'New Block';
                                        })
                                        ->defaultItems(0)
                                        ->addActionLabel('Add Block'),
                                ]),

                            Forms\Components\Section::make('HTML Preview')
                                ->schema([
                                    Forms\Components\Placeholder::make('generated_html_en_preview')
                                        ->label('Generated HTML Code')
                                        ->content(function (?Listicle $record): HtmlString {
                                            if (!$record || !$record->generated_html_en) {
                                                return new HtmlString('<p>HTML will be generated after saving...</p>');
                                            }

                                            return new HtmlString('<div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #f9fafb; max-height: 400px; overflow-y: auto;"><pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word; font-size: 12px;">' . htmlspecialchars($record->generated_html_en) . '</pre></div>');
                                        }),
                                ])
                                ->collapsible()
                                ->collapsed(),
                        ]),

                    Forms\Components\Tabs\Tab::make('German')
                        ->label(fn (Get $get) => ($get('title_de') ?? 'New Listicle').' - DE')
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
                                        ->disableToolbarButtons(['attachFiles', 'codeBlock'])
                                        ->columnSpanFull(),
                                    FileUpload::make('image_de')
                                        ->label('Beitragsbild')
                                        ->image()
                                        ->imageEditor()
                                        ->imageCropAspectRatio('16:9')
                                        ->imageResizeTargetWidth('1920')
                                        ->imageResizeTargetHeight('1080')
                                        ->imageResizeMode('cover')
                                        ->disk('public')
                                        ->directory('listicle-images')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                        ->rules(['dimensions:min_width=1200,min_height=675'])
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('meta_description_de')
                                        ->label('Meta Description')
                                        ->maxLength(160)
                                        ->helperText('Max. 160 Zeichen für SEO'),
                                ]),

                            Forms\Components\Section::make('Content Blocks')
                                ->description('Füge Locations, Links und andere Inhalte hinzu. Die Reihenfolge kann beliebig angepasst werden.')
                                ->schema([
                                    Forms\Components\Repeater::make('content_blocks_data_de')
                                        ->label('Inhalt (DE)')
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

                                            // Attraction Block Fields
                                            Forms\Components\Select::make('attraction_id')
                                                ->label('Attraction')
                                                ->options(\App\Models\Attraction::all()->pluck('name', 'id'))
                                                ->searchable()
                                                ->required()
                                                ->live()
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'location'),
                                            Forms\Components\RichEditor::make('custom_intro')
                                                ->label('Custom Intro')
                                                ->disableToolbarButtons(['attachFiles', 'codeBlock'])
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'location'),

                                            // Related Links Block Fields
                                            Forms\Components\TextInput::make('title')
                                                ->label('Block Titel')
                                                ->default('Das könnte Dich auch interessieren')
                                                ->live()
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'related_links'),
                                            Forms\Components\Repeater::make('links')
                                                ->label('Links')
                                                ->schema([
                                                    Forms\Components\TextInput::make('title')
                                                        ->label('Link Titel')
                                                        ->required(),
                                                    Forms\Components\TextInput::make('url')
                                                        ->label('URL')
                                                        ->url()
                                                        ->required(),
                                                ])
                                                ->columns(2)
                                                ->collapsible()
                                                ->visible(fn (Forms\Get $get) => $get('block_type') === 'related_links'),
                                        ])
                                        ->reorderable()
                                        ->collapsible()
                                        ->collapsed()
                                        ->itemLabel(function (array $state): ?string {
                                            if (($state['block_type'] ?? '') === 'location') {
                                                return 'Attraction: ' . (\App\Models\Attraction::find($state['attraction_id'])?->name ?? 'Unbekannt');
                                            }
                                            if (($state['block_type'] ?? '') === 'related_links') {
                                                return 'Weitere Links: ' . ($state['title'] ?? 'Unbenannt');
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
                                        ->content(function (?Listicle $record): HtmlString {
                                            if (!$record || !$record->generated_html_de) {
                                                return new HtmlString('<p>HTML wird nach dem Speichern generiert...</p>');
                                            }

                                            return new HtmlString('<div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #f9fafb; max-height: 400px; overflow-y: auto;"><pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word; font-size: 12px;">' . htmlspecialchars($record->generated_html_de) . '</pre></div>');
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
                Tables\Columns\TextColumn::make('title_en')
                    ->label('Title (EN)')
                    ->searchable()
                    ->sortable(),
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
            'index' => Pages\ListListicles::route('/'),
            'create' => Pages\CreateListicle::route('/create'),
            'edit' => Pages\EditListicle::route('/{record}/edit'),
        ];
    }
}
