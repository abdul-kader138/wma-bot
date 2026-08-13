<?php

namespace App\Filament\Resources\ContentItemResource\Pages;

use App\Filament\Resources\ContentItemResource;
use App\Services\Maria\ContentPackageService;
use Filament\Resources\Pages\CreateRecord;

class CreateContentItem extends CreateRecord
{
    protected static string $resource = ContentItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['source_hash'] = ContentPackageService::sourceHash($data['source_idea']);
        $data['status'] = 'idea';

        return $data;
    }
}
