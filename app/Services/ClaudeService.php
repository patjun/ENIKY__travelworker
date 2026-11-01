<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\Listicle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class ClaudeService
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private int $maxRetries = 3;

    public function __construct()
    {
        $this->apiKey = config('ai.anthropic.api_key');
        $this->baseUrl = config('ai.anthropic.base_url');
        $this->timeout = config('ai.anthropic.timeout');

        if (!$this->apiKey) {
            throw new \InvalidArgumentException('Anthropic API key is not configured. Please set ANTHROPIC_API_KEY in your .env file.');
        }
    }

    /**
     * Generate an intro text for a listicle using Claude AI.
     */
    public function generateIntro(string $language, Listicle $listicle): string
    {
        $settings = AiSetting::getInstance();

        $prompt = $this->renderPrompt($language, $listicle, $settings);

        return $this->sendMessage($prompt, $settings->max_tokens);
    }

    /**
     * Translate price texts between German and English using Claude AI.
     *
     * Texts that are empty in the source stay null in the result.
     *
     * @param  array{intro: ?string, footer: ?string, sections: list<array{name: ?string, rows: list<?string>}>}  $texts
     * @return array{intro: ?string, footer: ?string, sections: list<array{name: ?string, rows: list<?string>}>}
     *
     * @throws \RuntimeException when the response does not match the shape of $texts
     */
    public function translatePrices(array $texts, string $sourceLanguage, string $targetLanguage): array
    {
        $settings = AiSetting::getInstance();

        $languageNames = ['de' => 'German', 'en' => 'English'];
        $json = json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $prompt = "Translate the following admission price texts of a tourist attraction from {$languageNames[$sourceLanguage]} into {$languageNames[$targetLanguage]}.\n"
            ."Respond with JSON only, no explanation and no code fence. Keep exactly the same structure, the same keys and the same number of list entries.\n"
            ."Keep null values as null. Do not translate proper names, and keep numbers, currency symbols, dates and URLs unchanged.\n\n"
            .$json;

        $response = $this->sendMessage($prompt, max($settings->max_tokens, 4000));

        $translated = json_decode($this->stripCodeFence($response), true);

        if (! is_array($translated)) {
            throw new \RuntimeException('Claude returned no valid JSON for the translation.');
        }

        return $this->matchTranslationShape($texts, $translated);
    }

    /**
     * Send a single user message to the Claude API and return the text of the answer.
     */
    private function sendMessage(string $prompt, int $maxTokens): string
    {
        $settings = AiSetting::getInstance();

        return $this->executeWithRetry(function () use ($prompt, $settings, $maxTokens) {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/messages", [
                    'model' => $settings->model,
                    'max_tokens' => $maxTokens,
                    'temperature' => $settings->temperature,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Claude API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $response->throw();
            }

            $data = $response->json();

            if (! isset($data['content'][0]['text'])) {
                Log::error('Claude API response missing text content', [
                    'response' => $data,
                ]);
                throw new \RuntimeException('Invalid response from Claude API');
            }

            return $data['content'][0]['text'];
        });
    }

    /**
     * Remove a Markdown code fence that Claude may wrap around a JSON answer.
     */
    private function stripCodeFence(string $text): string
    {
        $text = trim($text);

        if (preg_match('/\A```[a-zA-Z]*\s*\n(.*)\n?```\z/s', $text, $matches) === 1) {
            return trim($matches[1]);
        }

        return $text;
    }

    /**
     * Check a decoded translation against the input shape and normalise it.
     *
     * @param  array{intro: ?string, footer: ?string, sections: list<array{name: ?string, rows: list<?string>}>}  $texts
     * @param  array<mixed>  $translated
     * @return array{intro: ?string, footer: ?string, sections: list<array{name: ?string, rows: list<?string>}>}
     */
    private function matchTranslationShape(array $texts, array $translated): array
    {
        $this->assertSameKeys($texts, $translated);

        if (! is_array($translated['sections']) || ! array_is_list($translated['sections']) || count($translated['sections']) !== count($texts['sections'])) {
            throw $this->shapeMismatch();
        }

        $sections = [];

        foreach ($texts['sections'] as $index => $section) {
            $translatedSection = $translated['sections'][$index];

            if (! is_array($translatedSection)) {
                throw $this->shapeMismatch();
            }

            $this->assertSameKeys($section, $translatedSection);

            if (! is_array($translatedSection['rows']) || ! array_is_list($translatedSection['rows']) || count($translatedSection['rows']) !== count($section['rows'])) {
                throw $this->shapeMismatch();
            }

            $sections[] = [
                'name' => $this->matchTranslatedText($section['name'], $translatedSection['name']),
                'rows' => array_map(
                    fn (?string $source, mixed $text): ?string => $this->matchTranslatedText($source, $text),
                    $section['rows'],
                    $translatedSection['rows'],
                ),
            ];
        }

        return [
            'intro' => $this->matchTranslatedText($texts['intro'], $translated['intro']),
            'footer' => $this->matchTranslatedText($texts['footer'], $translated['footer']),
            'sections' => $sections,
        ];
    }

    /**
     * @param  array<string, mixed>  $expected
     * @param  array<mixed>  $actual
     */
    private function assertSameKeys(array $expected, array $actual): void
    {
        $expectedKeys = array_keys($expected);
        $actualKeys = array_keys($actual);

        sort($expectedKeys);
        sort($actualKeys);

        if ($expectedKeys !== $actualKeys) {
            throw $this->shapeMismatch();
        }
    }

    /**
     * An empty source stays null, a filled source needs a string translation.
     */
    private function matchTranslatedText(?string $source, mixed $translated): ?string
    {
        if ($source === null) {
            return null;
        }

        if (! is_string($translated)) {
            throw $this->shapeMismatch();
        }

        return $translated;
    }

    private function shapeMismatch(): \RuntimeException
    {
        return new \RuntimeException('Claude returned a translation with an unexpected structure.');
    }

    /**
     * Render the prompt with listicle data.
     */
    private function renderPrompt(string $language, Listicle $listicle, AiSetting $settings): string
    {
        $promptTemplate = $language === 'de' ? $settings->prompt_de : $settings->prompt_en;
        $titleField = "title_{$language}";

        $locations = $this->getLocationNames($listicle, $language);

        return str_replace(
            ['{title}', '{locations}', '{language}'],
            [$listicle->$titleField ?? '', $locations, strtoupper($language)],
            $promptTemplate
        );
    }

    /**
     * Get location names from the listicle's content blocks.
     */
    private function getLocationNames(Listicle $listicle, string $language): string
    {
        $locations = $listicle->contentBlocks()
            ->where('language', $language)
            ->where('blockable_type', \App\Models\AttractionBlock::class)
            ->with('blockable.attraction')
            ->get()
            ->pluck('blockable.attraction.name')
            ->filter()
            ->unique()
            ->toArray();

        return implode(', ', $locations);
    }

    /**
     * Execute a callable with retry logic.
     */
    private function executeWithRetry(callable $callback, int $attempt = 1)
    {
        try {
            return $callback();
        } catch (RequestException $e) {
            if ($attempt >= $this->maxRetries) {
                Log::error('Claude API request failed after max retries', [
                    'attempts' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            $waitTime = pow(2, $attempt);
            Log::warning("Claude API request failed, retrying in {$waitTime}s (attempt {$attempt}/{$this->maxRetries})", [
                'error' => $e->getMessage(),
            ]);

            sleep($waitTime);

            return $this->executeWithRetry($callback, $attempt + 1);
        }
    }
}
