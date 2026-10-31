<?php

namespace App\Filament\Actions;

use App\Services\ClaudeService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class TranslatePricesAction
{
    private const LANGUAGE_NAMES = ['de' => 'Deutsch', 'en' => 'Englisch'];

    /**
     * Create a header action that translates the price texts into the target language.
     */
    public static function make(string $targetLanguage): Action
    {
        $sourceLanguage = static::otherLanguage($targetLanguage);
        $targetName = static::LANGUAGE_NAMES[$targetLanguage];
        $overwritesText = fn (Get $get): bool => static::hasTexts(static::stateFromForm($get), $targetLanguage);

        return Action::make('translatePricesTo'.ucfirst($targetLanguage))
            ->icon('heroicon-o-language')
            ->label("Nach {$targetName} übersetzen")
            ->color('gray')
            ->requiresConfirmation($overwritesText)
            ->modalHeading(fn (Get $get): ?string => $overwritesText($get) ? "Preistexte nach {$targetName} übersetzen?" : null)
            ->modalDescription(fn (Get $get): ?string => $overwritesText($get)
                ? "Vorhandene Preistexte auf {$targetName} werden im Formular überschrieben. Gespeichert wird erst mit „Speichern“."
                : null)
            ->modalSubmitActionLabel('Übersetzen')
            ->action(function (Get $get, Set $set) use ($sourceLanguage, $targetLanguage, $targetName): void {
                $state = static::stateFromForm($get);

                if (! static::hasTexts($state, $sourceLanguage)) {
                    Notification::make()
                        ->warning()
                        ->title('Nichts zu übersetzen')
                        ->body('In der Quellsprache sind keine Preistexte vorhanden.')
                        ->send();

                    return;
                }

                try {
                    $translated = app(ClaudeService::class)->translatePrices(
                        static::extractTexts($state, $sourceLanguage),
                        $sourceLanguage,
                        $targetLanguage,
                    );

                    $result = static::applyTranslation($state, $translated, $targetLanguage);
                } catch (\Throwable $e) {
                    Log::error('AI price translation failed', [
                        'target_language' => $targetLanguage,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    Notification::make()
                        ->danger()
                        ->title('Übersetzung fehlgeschlagen')
                        ->body('Fehler: '.$e->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                foreach (array_keys($state) as $field) {
                    $set($field, $result[$field]);
                }

                Notification::make()
                    ->success()
                    ->title('Preistexte übersetzt')
                    ->body("Die Texte auf {$targetName} wurden im Formular eingetragen. Bitte prüfen und speichern.")
                    ->send();
            });
    }

    /**
     * Collect the translatable texts of a language, with empty texts as null.
     *
     * @param  array<string, mixed>  $state  Price form state: prices, price_intro, en_price_intro, price_footer, en_price_footer
     * @return array{intro: ?string, footer: ?string, sections: list<array{name: ?string, rows: list<?string>}>}
     */
    public static function extractTexts(array $state, string $language): array
    {
        $sections = [];

        foreach (array_values($state['prices'] ?? []) as $section) {
            $sections[] = [
                'name' => static::textOrNull($section["name_{$language}"] ?? null),
                'rows' => array_map(
                    fn (array $row): ?string => static::textOrNull($row["label_{$language}"] ?? null),
                    array_values($section['rows'] ?? []),
                ),
            ];
        }

        return [
            'intro' => static::textOrNull($state[static::introField($language)] ?? null),
            'footer' => static::textOrNull($state[static::footerField($language)] ?? null),
            'sections' => $sections,
        ];
    }

    /**
     * Write a translation into the fields of the target language.
     *
     * Entries are matched by position, because new repeater items have no id yet. A null entry leaves the
     * existing value of the target field untouched.
     *
     * @param  array<string, mixed>  $state  Price form state, see extractTexts()
     * @param  array{intro: ?string, footer: ?string, sections: list<array{name: ?string, rows: list<?string>}>}  $translated
     * @return array<string, mixed>
     */
    public static function applyTranslation(array $state, array $translated, string $language): array
    {
        $introField = static::introField($language);
        $footerField = static::footerField($language);

        $state[$introField] = $translated['intro'] ?? ($state[$introField] ?? null);
        $state[$footerField] = $translated['footer'] ?? ($state[$footerField] ?? null);

        $sectionPosition = 0;

        foreach ($state['prices'] ?? [] as $sectionKey => $section) {
            $translatedSection = $translated['sections'][$sectionPosition++] ?? [];

            if (($translatedSection['name'] ?? null) !== null) {
                $state['prices'][$sectionKey]["name_{$language}"] = $translatedSection['name'];
            }

            $rowPosition = 0;

            foreach ($section['rows'] ?? [] as $rowKey => $row) {
                $label = $translatedSection['rows'][$rowPosition++] ?? null;

                if ($label !== null) {
                    $state['prices'][$sectionKey]['rows'][$rowKey]["label_{$language}"] = $label;
                }
            }
        }

        return $state;
    }

    /**
     * Whether the state holds any text in the given language.
     *
     * @param  array<string, mixed>  $state
     */
    private static function hasTexts(array $state, string $language): bool
    {
        $texts = static::extractTexts($state, $language);

        return $texts['intro'] !== null
            || $texts['footer'] !== null
            || collect($texts['sections'])->contains(
                fn (array $section): bool => $section['name'] !== null || collect($section['rows'])->contains(fn (?string $row): bool => $row !== null)
            );
    }

    /**
     * @return array<string, mixed>
     */
    private static function stateFromForm(Get $get): array
    {
        return [
            'prices' => $get('prices') ?? [],
            'price_intro' => $get('price_intro'),
            'en_price_intro' => $get('en_price_intro'),
            'price_footer' => $get('price_footer'),
            'en_price_footer' => $get('en_price_footer'),
        ];
    }

    private static function textOrNull(mixed $text): ?string
    {
        return is_string($text) && trim($text) !== '' ? $text : null;
    }

    private static function otherLanguage(string $language): string
    {
        return $language === 'de' ? 'en' : 'de';
    }

    private static function introField(string $language): string
    {
        return $language === 'en' ? 'en_price_intro' : 'price_intro';
    }

    private static function footerField(string $language): string
    {
        return $language === 'en' ? 'en_price_footer' : 'price_footer';
    }
}
