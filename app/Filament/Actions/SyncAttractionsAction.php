<?php

namespace App\Filament\Actions;

use App\Jobs\SyncAttractionsToWordPressJob;
use App\Models\Attraction;
use App\Services\AttractionSyncReport;
use App\Services\AttractionSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class SyncAttractionsAction
{
    private const SYNC_ROLES = ['super_admin', 'admin', 'editor'];

    private const SYNC_ALL_ROLES = ['super_admin', 'admin'];

    /**
     * Button on the edit page that transfers the saved attraction at once.
     */
    public static function page(): Action
    {
        return Action::make('syncNow')
            ->label('Sync now')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->visible(fn (): bool => static::userHasRole(self::SYNC_ROLES))
            ->action(fn (Attraction $record) => static::syncNow($record));
    }

    /**
     * Row button in the list that transfers one attraction at once.
     */
    public static function row(): TableAction
    {
        return TableAction::make('syncNow')
            ->label('Sync')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->visible(fn (): bool => static::userHasRole(self::SYNC_ROLES))
            ->action(fn (Attraction $record) => static::syncNow($record));
    }

    /**
     * Bulk action that transfers the selected attractions in the background.
     */
    public static function selected(): BulkAction
    {
        return BulkAction::make('syncSelected')
            ->label('Sync')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->visible(fn (): bool => static::userHasRole(self::SYNC_ROLES))
            ->deselectRecordsAfterCompletion()
            ->action(fn (Collection $records) => static::queue($records->modelKeys()));
    }

    /**
     * Header button that transfers everything changed since the last sync.
     */
    public static function all(): Action
    {
        return Action::make('syncAll')
            ->label('Sync all')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->visible(fn (): bool => static::userHasRole(self::SYNC_ALL_ROLES))
            ->requiresConfirmation()
            ->modalHeading('Sync all attractions?')
            ->modalDescription('All changes since the last sync are sent to WordPress in the background. Affected posts get a new modified date.')
            ->modalSubmitActionLabel('Yes, sync')
            ->action(fn () => static::queue(static::allAttractionIds()));
    }

    /**
     * Header button for structural widget changes: renews and sends every widget without dating posts.
     */
    public static function refreshAllWidgets(): Action
    {
        return Action::make('refreshAllWidgets')
            ->label('Renew all widgets')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (): bool => static::userHasRole(self::SYNC_ALL_ROLES))
            ->requiresConfirmation()
            ->modalHeading('Renew all widgets?')
            ->modalDescription('All widgets are regenerated and sent to WordPress in the background. The cache of affected posts is cleared, their modified date stays the same.')
            ->modalSubmitActionLabel('Yes, renew')
            ->action(fn () => static::queue(static::allAttractionIds(), refreshWidgets: true));
    }

    /**
     * Build the notification for a transfer result, with line breaks kept.
     */
    public static function notification(string $title, string $body, string $status): Notification
    {
        return Notification::make()
            ->title($title)
            ->body(nl2br(e($body), false))
            ->status($status);
    }

    private static function syncNow(Attraction $attraction): void
    {
        $report = new AttractionSyncReport;
        app(AttractionSyncService::class)->syncToAllLanguages($attraction, $report);

        $wasSent = $report->total(AttractionSyncReport::SYNCED) > 0;

        if ($report->hasProblems()) {
            static::notification(
                $wasSent ? 'Partially synced' : 'Sync failed',
                $report->summary(),
                $wasSent ? 'warning' : 'danger',
            )->persistent()->send();

            return;
        }

        if (! $wasSent) {
            static::notification('No changes', 'Nothing new since the last sync.', 'info')->send();

            return;
        }

        static::notification('Synced', $report->summary(), 'success')->send();
    }

    /**
     * @param  array<int, int>  $attractionIds
     */
    private static function queue(array $attractionIds, bool $refreshWidgets = false): void
    {
        if ($attractionIds === []) {
            static::notification('No attractions found', 'There is nothing to sync.', 'warning')->send();

            return;
        }

        SyncAttractionsToWordPressJob::dispatch($attractionIds, auth()->id(), $refreshWidgets);

        $count = count($attractionIds);

        static::notification('Sync started', "{$count} attraction(s) are syncing in the background. The result appears in the notification bell.", 'info')->send();
    }

    /**
     * @return array<int, int>
     */
    private static function allAttractionIds(): array
    {
        return Attraction::query()->orderBy('id')->pluck('id')->all();
    }

    /**
     * @param  array<int, string>  $roles
     */
    private static function userHasRole(array $roles): bool
    {
        return auth()->user()?->hasAnyRole($roles) ?? false;
    }
}
