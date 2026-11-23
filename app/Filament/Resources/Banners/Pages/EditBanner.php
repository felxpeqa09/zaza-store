<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Resources\Banners\BannerResource;
use App\Models\Banner;
use App\Services\Catalog\BannerEditor;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $saved = app(BannerEditor::class)->save($record, $data);
        $record->setRawAttributes($saved->getAttributes(), true);

        return $record;
    }

    protected function afterSave(): void
    {
        $this->refreshFormData(['imagen']);
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->using(fn (Banner $record) => app(BannerEditor::class)->delete($record))];
    }
}
