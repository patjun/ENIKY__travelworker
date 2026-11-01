<?php

namespace App\Filament\Resources\AttractionResource\Pages;

use App\Filament\Actions\SyncAttractionsAction;
use App\Filament\Resources\AttractionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttractions extends ListRecords
{
    protected static string $resource = AttractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SyncAttractionsAction::refreshAllWidgets(),
            SyncAttractionsAction::all(),
            Actions\CreateAction::make(),
        ];
    }
}
