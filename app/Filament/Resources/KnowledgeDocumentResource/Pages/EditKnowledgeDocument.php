<?php

namespace App\Filament\Resources\KnowledgeDocumentResource\Pages;

use App\Filament\Resources\KnowledgeDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeDocument extends EditRecord
{
    protected static string $resource = KnowledgeDocumentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['source_disk'] = $data['source_path'] ? 'local' : null;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
