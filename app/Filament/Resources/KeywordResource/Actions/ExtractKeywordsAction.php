<?php

namespace App\Filament\Resources\KeywordResource\Actions;

use App\Models\Keyword;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExtractKeywordsAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'extract_keywords';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Extract Keywords');
        $this->icon('heroicon-o-document-duplicate');
        $this->requiresConfirmation();
        $this->action(function (Collection $records) {
            $extractedCount = 0;

            foreach ($records as $record) {
                $taskGetOutput = json_decode($record->task_get_output, true);
                if (!is_array($taskGetOutput)) {
                    continue;
                }

                foreach ($taskGetOutput as $item) {
                    $keyword = Keyword::updateOrCreate(
                        ['keyword' => $item['keyword']],
                        [
                            'location_code' => $item['location_code'] ?? null,
                            'language_code' => $item['language_code'] ?? null,
                            'search_partners' => $item['search_partners'] ?? false,
                            'competition' => $item['competition'] ?? null,
                            'competition_index' => $item['competition_index'] ?? null,
                            'search_volume' => $item['search_volume'] ?? null,
                            'low_top_of_page_bid' => $item['low_top_of_page_bid'] ?? null,
                            'high_top_of_page_bid' => $item['high_top_of_page_bid'] ?? null,
                            'cpc' => $item['cpc'] ?? null,
                            'monthly_searches' => json_encode($item['monthly_searches'] ?? null),
                            'keyword_annotations' => json_encode($item['keyword_annotations'] ?? null),
                        ]
                    );

                    // Add relationship to parent keyword if it exists and is different
                    if ($record->keyword !== $keyword->keyword) {
                        DB::table('keyword_parent')->updateOrInsert(
                            ['keyword_id' => $keyword->id, 'parent_id' => $record->id],
                            ['created_at' => now(), 'updated_at' => now()]
                        );
                    }

                    if ($keyword->wasRecentlyCreated) {
                        $extractedCount++;
                    }
                }
            }

            Notification::make()
                        ->title("Extraction Complete")
                        ->body("{$extractedCount} new keywords extracted.")
                        ->success()
                        ->send();
        });
    }

    public static function makeAction(): Action
    {
        return Action::make('extract_keywords')
                     ->label('Extract Keywords')
                     ->icon('heroicon-o-document-duplicate')
                     ->requiresConfirmation()
                     ->action(function (Keyword $record) {
                         $taskGetOutput = json_decode($record->task_get_output, true);
                         if (!is_array($taskGetOutput)) {
                             Notification::make()
                                         ->title("Extraction Failed")
                                         ->body("Invalid task_get_output data.")
                                         ->danger()
                                         ->send();
                             return;
                         }

                         $extractedCount = 0;

                         foreach ($taskGetOutput as $item) {
                             $keyword = Keyword::updateOrCreate(
                                 ['keyword' => $item['keyword']],
                                 [
                                     'location_code' => $item['location_code'] ?? null,
                                     'language_code' => $item['language_code'] ?? null,
                                     'search_partners' => $item['search_partners'] ?? false,
                                     'competition' => $item['competition'] ?? null,
                                     'competition_index' => $item['competition_index'] ?? null,
                                     'search_volume' => $item['search_volume'] ?? null,
                                     'low_top_of_page_bid' => $item['low_top_of_page_bid'] ?? null,
                                     'high_top_of_page_bid' => $item['high_top_of_page_bid'] ?? null,
                                     'cpc' => $item['cpc'] ?? null,
                                     'monthly_searches' => json_encode($item['monthly_searches'] ?? null),
                                     'keyword_annotations' => json_encode($item['keyword_annotations'] ?? null),
                                 ]
                             );

                             // Add relationship to parent keyword if it exists and is different
                             if ($record->keyword !== $keyword->keyword) {
                                 DB::table('keyword_parent')->updateOrInsert(
                                     ['keyword_id' => $keyword->id, 'parent_id' => $record->id],
                                     ['created_at' => now(), 'updated_at' => now()]
                                 );
                             }

                             if ($keyword->wasRecentlyCreated) {
                                 $extractedCount++;
                             }
                         }

                         Notification::make()
                                     ->title("Extraction Complete")
                                     ->body("{$extractedCount} new keywords extracted.")
                                     ->success()
                                     ->send();
                     });
    }
}
