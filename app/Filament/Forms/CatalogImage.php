<?php

namespace App\Filament\Forms;

use App\View\ProductDisplay;
use Filament\Forms\Components\FileUpload;

final class CatalogImage
{
    public static function make(string $name): FileUpload
    {
        return FileUpload::make($name)
            ->disk(fn () => config('zaza.catalog_disk'))
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(4096)
            ->storeFiles(false)
            ->fetchFileInformation(false)
            ->getUploadedFileUsing(fn (string $file): array => [
                'name' => basename($file), 'size' => 0, 'type' => null, 'url' => ProductDisplay::image($file),
            ]);
    }
}
