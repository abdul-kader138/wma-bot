<?php

namespace App\Filament\Resources\KnowledgeDocumentResource\Pages;

use App\Filament\Resources\KnowledgeDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeDocument extends CreateRecord
{
    protected static string $resource = KnowledgeDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source_disk'] = $data['source_path'] ? 'local' : null;

        return $data;
    }
}
