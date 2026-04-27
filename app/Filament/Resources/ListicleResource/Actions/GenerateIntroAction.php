<?php

namespace App\Filament\Resources\ListicleResource\Actions;

use App\Services\ClaudeService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class GenerateIntroAction
{
    /**
     * Create an action for generating intro text with AI.
     */
    public static function make(string $language): Action
    {
        $labelMap = [
            'de' => 'Deutsches Intro generieren',
            'en' => 'Englisches Intro generieren',
        ];

        return Action::make('generateIntro' . ucfirst($language))
            ->icon('heroicon-o-sparkles')
            ->label($labelMap[$language] ?? 'Generate Intro')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Intro mit KI generieren?')
            ->modalDescription('Der generierte Text wird unterhalb des bestehenden Inhalts eingefügt.')
            ->modalSubmitActionLabel('Generieren')
            ->action(function ($livewire) use ($language) {
                try {
                    // Show loading notification
                    Notification::make()
                        ->title('KI-Generierung läuft...')
                        ->body('Bitte warten Sie, während der Intro-Text generiert wird.')
                        ->info()
                        ->send();

                    // Get the listicle record
                    $record = $livewire->getRecord();

                    if (!$record) {
                        throw new \RuntimeException('Kein Listicle-Datensatz gefunden.');
                    }

                    // Generate intro using Claude AI
                    $claudeService = app(ClaudeService::class);
                    $generatedText = $claudeService->generateIntro($language, $record);

                    // Get current intro value
                    $introField = "intro_{$language}";
                    $currentValue = $livewire->data[$introField] ?? '';

                    // Append generated text with proper HTML formatting
                    $separator = $currentValue ? '<br><br>' : '';
                    $newValue = $currentValue . $separator . $generatedText;

                    // Update the data
                    $livewire->data[$introField] = $newValue;

                    // Show success notification
                    Notification::make()
                        ->success()
                        ->title('Intro erfolgreich generiert')
                        ->body('Der generierte Text wurde hinzugefügt. Bitte speichern Sie das Formular.')
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
