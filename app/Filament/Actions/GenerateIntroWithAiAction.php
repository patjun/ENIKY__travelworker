<?php

namespace App\Filament\Actions;

use App\Services\ClaudeService;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class GenerateIntroWithAiAction
{
    /**
     * Create a suffix action for generating intro text with AI.
     */
    public static function make(string $language): Action
    {
        return Action::make('generateIntro')
            ->icon('heroicon-o-sparkles')
            ->label('Mit KI generieren')
            ->tooltip('Intro-Text mit Claude AI generieren')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Intro mit KI generieren?')
            ->modalDescription('Der generierte Text wird unterhalb des bestehenden Inhalts eingefügt.')
            ->modalSubmitActionLabel('Generieren')
            ->action(function ($component, $livewire) use ($language) {
                try {
                    // Show loading notification
                    Notification::make()
                        ->title('KI-Generierung läuft...')
                        ->body('Bitte warten Sie, während der Intro-Text generiert wird.')
                        ->info()
                        ->send();

                    // Get the listicle record from the form
                    $record = $livewire->getRecord();

                    if (!$record) {
                        throw new \RuntimeException('Kein Listicle-Datensatz gefunden.');
                    }

                    // Generate intro using Claude AI
                    $claudeService = app(ClaudeService::class);
                    $generatedText = $claudeService->generateIntro($language, $record);

                    // Get current value from the component
                    $currentValue = $component->getState() ?? '';

                    // Append generated text with proper HTML formatting
                    $separator = $currentValue ? '<br><br>' : '';
                    $newValue = $currentValue . $separator . $generatedText;

                    // Update the component state
                    $component->state($newValue);

                    // Show success notification
                    Notification::make()
                        ->success()
                        ->title('Intro erfolgreich generiert')
                        ->body('Der generierte Text wurde hinzugefügt.')
                        ->send();

                } catch (\Exception $e) {
                    Log::error('AI intro generation failed', [
                        'language' => $language,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    Notification::make()
                        ->danger()
                        ->title('Generierung fehlgeschlagen')
                        ->body('Fehler: ' . $e->getMessage())
                        ->persistent()
                        ->send();
                }
            });
    }
}
