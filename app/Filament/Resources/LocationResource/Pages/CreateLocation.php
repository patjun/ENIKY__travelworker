<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use App\Jobs\ProcessDataForSeoOrchestrator;
use App\Models\Location;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\HtmlString;

class CreateLocation extends CreateRecord
{
    protected static string $resource = LocationResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('place_id')
                    ->label('Google Places ID')
                    ->helperText(new HtmlString('<a href="https://developers.google.com/maps/documentation/places/web-service/place-id?hl=de" target="_blank" rel="noopener">Klick zum Place ID Finder</a>'))
                    ->required()
                    ->unique(Location::class, 'place_id')
                    ->validationMessages([
                        'unique' => 'Diese Google Places ID ist bereits vorhanden.',
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name'] = 'Neue Location';
        $data['latitude'] = 52.520008;
        $data['longitude'] = 13.404954;

        return $data;
    }

    protected function afterCreate(): void
    {
        if (!empty($this->record->place_id)) {
            ProcessDataForSeoOrchestrator::dispatch($this->record);

            $this->record->update([
                'job_status' => 'pending',
                'en_job_status' => 'pending'
            ]);
        }
    }
}
