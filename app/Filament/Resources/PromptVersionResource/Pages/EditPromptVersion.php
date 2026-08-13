<?php

namespace App\Filament\Resources\PromptVersionResource\Pages;

use App\Filament\Resources\PromptVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromptVersion extends EditRecord
{
    protected static string $resource = PromptVersionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PromptVersionResource::prepareData($data, $this->record);
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
