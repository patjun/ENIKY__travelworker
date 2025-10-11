<?php

namespace App\Filament\Resources\AccessibilityAttributeResource\Pages;

use App\Filament\Resources\AccessibilityAttributeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccessibilityAttribute extends EditRecord
{
    protected static string $resource = AccessibilityAttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
