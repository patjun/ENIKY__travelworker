<?php

namespace App\Services;

class AttractionSyncReport
{
    public const LANGUAGES = ['de', 'en'];

    public const SYNCED = 'synced';

    public const UNCHANGED = 'unchanged';

    public const SKIPPED = 'skipped';

    private const MAX_LISTED_FAILURES = 5;

    /**
     * @var array<string, array<string, int>>
     */
    private array $counts = [];

    /**
     * @var array<int, array{name: string, language: string, reason: string}>
     */
    public array $failures = [];

    /**
     * @var array<string, string>
     */
    public array $loginFailures = [];

    public function add(string $language, string $outcome, int $amount = 1): void
    {
        $this->counts[$language][$outcome] = ($this->counts[$language][$outcome] ?? 0) + $amount;
    }

    public function total(string $outcome): int
    {
        return (int) array_sum(array_column($this->counts, $outcome));
    }

    public function addFailure(string $name, string $language, string $reason): void
    {
        $this->failures[] = ['name' => $name, 'language' => $language, 'reason' => $reason];
    }

    public function addLoginFailure(string $language, string $reason): void
    {
        $this->loginFailures[$language] = $reason;
    }

    public function hasLoginFailed(string $language): bool
    {
        return isset($this->loginFailures[$language]);
    }

    public function allLanguagesFailedLogin(): bool
    {
        return count($this->loginFailures) === count(self::LANGUAGES);
    }

    public function hasProblems(): bool
    {
        return $this->failures !== [] || $this->loginFailures !== [] || $this->total(self::SKIPPED) > 0;
    }

    public function summary(): string
    {
        $lines = array_values(array_filter(array_map($this->languageSummary(...), self::LANGUAGES)));

        if ($lines === []) {
            $lines = ['0 '.self::SYNCED];
        }

        foreach ($this->loginFailures as $language => $reason) {
            $lines[] = "Login {$language} failed: {$reason}";
        }

        foreach (array_slice($this->failures, 0, self::MAX_LISTED_FAILURES) as $failure) {
            $lines[] = "{$failure['name']} ({$failure['language']}): {$failure['reason']}";
        }

        $unlistedFailures = count($this->failures) - self::MAX_LISTED_FAILURES;

        if ($unlistedFailures > 0) {
            $lines[] = "and {$unlistedFailures} more";
        }

        return implode("\n", $lines);
    }

    private function languageSummary(string $language): ?string
    {
        $failed = count(array_filter($this->failures, fn (array $failure): bool => $failure['language'] === $language));
        $parts = [];

        foreach ([self::SYNCED, self::UNCHANGED] as $outcome) {
            if (($this->counts[$language][$outcome] ?? 0) > 0) {
                $parts[] = $this->counts[$language][$outcome].' '.$outcome;
            }
        }

        if ($failed > 0) {
            $parts[] = "{$failed} failed";
        }

        if (($this->counts[$language][self::SKIPPED] ?? 0) > 0) {
            $parts[] = $this->counts[$language][self::SKIPPED].' '.self::SKIPPED;
        }

        return $parts === [] ? null : "{$language}: ".implode(', ', $parts);
    }
}
