<?php

namespace App\Filament\Resources\Banners;

use App\Filament\Forms\CatalogImage;
use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Banners\Pages\ListBanners;
use App\Models\Banner;
use App\View\ProductDisplay;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $modelLabel = 'banner';

    protected static ?string $pluralModelLabel = 'banners';

    protected static ?string $navigationLabel = 'Banners';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $recordTitleAttribute = 'texto';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            CatalogImage::make('imagen')->label('Imagen')->required()->columnSpanFull(),
            TextInput::make('texto')->label('Texto')->maxLength(255),
            TextInput::make('enlace')->label('Enlace')->maxLength(255),
            TextInput::make('orden')->label('Orden')->integer()->minValue(0)->default(0)->required(),
            Toggle::make('activo')->label('Activo')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('orden')->columns([
            ImageColumn::make('imagen')->label('Imagen')->state(fn (Banner $record) => ProductDisplay::image($record->imagen)),
            TextColumn::make('texto')->label('Texto')->searchable(),
            TextColumn::make('orden')->label('Orden')->sortable(),
            IconColumn::make('activo')->label('Activo')->boolean(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBanners::route('/'),
            'create' => CreateBanner::route('/create'),
            'edit' => EditBanner::route('/{record}/edit'),
        ];
    }
}
