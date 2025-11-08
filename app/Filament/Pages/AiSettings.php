<?php

namespace App\Filament\Pages;

use App\Models\AiSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AiSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.ai-settings';

    protected static ?string $navigationLabel = 'AI-Einstellungen';

    protected static ?string $title = 'AI-Einstellungen';

    protected static ?string $navigationGroup = 'Einstellungen';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = AiSetting::getInstance();
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Deutsch')
                            ->schema([
                                Forms\Components\Textarea::make('prompt_de')
                                    ->label('Prompt für deutsches Intro')
                                    ->rows(10)
                                    ->helperText('Verfügbare Platzhalter: {title}, {locations}, {language}')
                                    ->required(),
                            ]),
                        Forms\Components\Tabs\Tab::make('English')
                            ->schema([
                                Forms\Components\Textarea::make('prompt_en')
                                    ->label('Prompt für englisches Intro')
                                    ->rows(10)
                                    ->helperText('Available placeholders: {title}, {locations}, {language}')
                                    ->required(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Allgemeine Einstellungen')
                            ->schema([
                                Forms\Components\Select::make('model')
                                    ->label('Claude Modell')
                                    ->options([
                                        'claude-sonnet-4-5' => 'Claude 3.5 Sonnet (Empfohlen)',
                                        'claude-haiku-4-5' => 'Claude 3.5 Haiku (Schneller)',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('max_tokens')
                                    ->label('Maximale Tokens')
                                    ->numeric()
                                    ->minValue(100)
                                    ->maxValue(4096)
                                    ->required()
                                    ->helperText('Maximale Anzahl an Tokens, die generiert werden sollen (100-4096)'),
                                Forms\Components\TextInput::make('temperature')
                                    ->label('Temperature')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.1)
                                    ->required()
                                    ->helperText('Kreativität der Antworten (0 = konservativ, 1 = kreativ)'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = AiSetting::getInstance();
        $settings->update($data);

        Notification::make()
            ->success()
            ->title('Einstellungen gespeichert')
            ->body('Die AI-Einstellungen wurden erfolgreich gespeichert.')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Speichern')
                ->submit('save'),
        ];
    }
}
