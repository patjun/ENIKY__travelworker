<?php

namespace App\Jobs;

use App\Filament\Actions\SyncAttractionsAction;
use App\Models\Attraction;
use App\Models\User;
use App\Services\AttractionSyncReport;
use App\Services\AttractionSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Transfers attractions in the background and reports the result to the user.
 *
 * A worker hands a job out again after the queue's retry_after of 90 seconds, so
 * the job stops after its time budget and queues itself with the remaining ids.
 */
class SyncAttractionsToWordPressJob implements ShouldQueue
{
    use Queueable;

    private const TIME_BUDGET_SECONDS = 45;

    public int $tries = 1;

    public int $timeout = 85;

    /**
     * @param  array<int, int>  $attractionIds
     */
    public function __construct(
        public array $attractionIds,
        public int $userId,
        public bool $refreshWidgets = false,
        public AttractionSyncReport $report = new AttractionSyncReport,
    ) {}

    public function handle(AttractionSyncService $syncService): void
    {
        $startedAt = now();
        $remainingIds = $this->attractionIds;

        while ($remainingIds !== [] && $startedAt->diffInSeconds(now(), true) < self::TIME_BUDGET_SECONDS) {
            if ($this->report->allLanguagesFailedLogin()) {
                foreach (AttractionSyncReport::LANGUAGES as $language) {
                    $this->report->add($language, AttractionSyncReport::SKIPPED, count($remainingIds));
                }

                $remainingIds = [];

                break;
            }

            $attraction = Attraction::find(array_shift($remainingIds));

            if ($attraction !== null) {
                $syncService->syncToAllLanguages($attraction, $this->report, $this->refreshWidgets);
            }
        }

        if ($remainingIds !== []) {
            self::dispatch($remainingIds, $this->userId, $this->refreshWidgets, $this->report);

            return;
        }

        $this->notifyUser(
            $this->refreshWidgets ? 'Widgets renewed' : 'Sync finished',
            $this->report->summary(),
            $this->report->hasProblems() ? 'warning' : 'success',
        );
    }

    public function failed(?Throwable $exception): void
    {
        $this->notifyUser(
            $this->refreshWidgets ? 'Widget renewal aborted' : 'Sync aborted',
            trim($this->report->summary()."\n".$exception?->getMessage()),
            'danger',
        );
    }

    private function notifyUser(string $title, string $body, string $status): void
    {
        $user = User::find($this->userId);

        if ($user !== null) {
            SyncAttractionsAction::notification($title, $body, $status)->sendToDatabase($user);
        }
    }
}
