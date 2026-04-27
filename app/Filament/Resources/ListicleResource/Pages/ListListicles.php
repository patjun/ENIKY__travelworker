<?php

namespace App\Filament\Resources\ListicleResource\Pages;

use App\Filament\Resources\ListicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListListicles extends ListRecords
{
    protected static string $resource = ListicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
