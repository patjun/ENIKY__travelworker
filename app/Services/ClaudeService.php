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

        return $this->executeWithRetry(function () use ($prompt, $settings) {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/messages", [
                    'model' => $settings->model,
                    'max_tokens' => $settings->max_tokens,
                    'temperature' => $settings->temperature,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('Claude API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $response->throw();
            }

            $data = $response->json();

            if (!isset($data['content'][0]['text'])) {
                Log::error('Claude API response missing text content', [
                    'response' => $data,
                ]);
                throw new \RuntimeException('Invalid response from Claude API');
            }

            return $data['content'][0]['text'];
        });
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
