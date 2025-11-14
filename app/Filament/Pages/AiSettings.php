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

    protected static ?string $navigationLabel = 'AI-Settings';

    protected static ?string $title = 'AI-Settings';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view ai_settings') ?? false;
    }

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
                        Forms\Components\Tabs\Tab::make('English')
                            ->schema([
                                Forms\Components\Textarea::make('prompt_en')
                                    ->label('Prompt for English Intro')
                                    ->rows(10)
                                    ->helperText('Available placeholders: {title}, {locations}, {language}')
                                    ->required(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Deutsch')
                            ->schema([
                                Forms\Components\Textarea::make('prompt_de')
                                    ->label('Prompt for German Intro')
                                    ->rows(10)
                                    ->helperText('Available placeholders: {title}, {locations}, {language}')
                                    ->required(),
                            ]),
                        Forms\Components\Tabs\Tab::make('General Settings')
                            ->schema([
                                Forms\Components\Select::make('model')
                                    ->label('Claude Model')
                                    ->options([
                                        'claude-sonnet-4-5' => 'Claude 3.5 Sonnet (Recommended)',
                                        'claude-haiku-4-5' => 'Claude 3.5 Haiku (Faster)',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('max_tokens')
                                    ->label('Max Tokens')
                                    ->numeric()
                                    ->minValue(100)
                                    ->maxValue(4096)
                                    ->required()
                                    ->helperText('Maximum number of tokens to generate (100-4096)'),
                                Forms\Components\TextInput::make('temperature')
                                    ->label('Temperature')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.1)
                                    ->required()
                                    ->helperText('Creativity of responses (0 = conservative, 1 = creative)'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        if (!auth()->user()?->can('edit ai_settings')) {
            Notification::make()
                ->danger()
                ->title('Access Denied')
                ->body('You do not have permission to edit AI settings.')
                ->send();
            return;
        }

        $data = $this->form->getState();

        $settings = AiSetting::getInstance();
        $settings->update($data);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('AI Settings have been successfully saved.')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save')
                ->submit('save'),
        ];
    }
}
