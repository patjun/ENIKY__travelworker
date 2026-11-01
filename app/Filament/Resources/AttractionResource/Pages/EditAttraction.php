<?php

namespace App\Filament\Resources\AttractionResource\Pages;

use App\Filament\Actions\SyncAttractionsAction;
use App\Filament\Resources\AttractionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttraction extends EditRecord
{
    protected static string $resource = AttractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SyncAttractionsAction::page(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Generate widgets after saving
        $this->record->generateWidgets();
        $this->record->save();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Attraction saved and widgets generated';
    }
}
