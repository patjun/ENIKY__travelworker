<?php

namespace App\Filament\Resources\KeywordResource\Actions;

use App\Http\Controllers\DFSKeywordForKeywordController;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class GetKeywordForKeywordDataAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'get_keyword_for_keyword_data';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Get Keyword data');
        $this->icon('heroicon-o-cloud-arrow-down');
        $this->requiresConfirmation();
        $this->action(function (Model $record) {
            $controller = app()->make(DFSKeywordForKeywordController::class);
            $request = new Request();

            try {
                $response = $controller->postTask($request, $record->keyword);

                if ($response->getStatusCode() === 200) {
                    Notification::make()
                                ->title('Keyword For Keyword Data Retrieved Successfully')
                                ->success()
                                ->send();
                } else {
                    Notification::make()
                                ->title('Failed to Retrieve Keyword For Keyword Data')
                                ->danger()
                                ->body('Unexpected response from the server.')
                                ->send();
                }
            } catch (\Exception $e) {
                Notification::make()
                            ->title('Error Retrieving Keyword For Keyword Data')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
            }
        });
    }
}
