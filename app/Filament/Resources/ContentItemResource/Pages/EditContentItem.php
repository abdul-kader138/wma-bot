<?php

namespace App\Filament\Resources\ContentItemResource\Pages;

use App\Filament\Resources\ContentItemResource;
use App\Services\Maria\ContentPackageService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContentItem extends EditRecord
{
    protected static string $resource = ContentItemResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newHash = ContentPackageService::sourceHash($data['source_idea']);
        if ($newHash !== $this->record->source_hash || $data['brand'] !== $this->record->brand || $data['core_claims'] !== $this->record->core_claims) {
            $data += ['master_draft' => null, 'derivatives' => null, 'claim_verification' => null, 'workflow_run_id' => null, 'generated_at' => null];
            $data['status'] = 'idea';
        }
        $data['source_hash'] = $newHash;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
