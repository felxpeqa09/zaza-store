<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Resources\Banners\BannerResource;
use App\Services\Catalog\BannerEditor;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBanner extends CreateRecord
{
    protected static string $resource = BannerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(BannerEditor::class)->save(null, $data);
    }
}
