<?php

namespace App\Services\Catalog;

use App\Models\Banner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class BannerEditor
{
    public static function safeLink(?string $value): ?string
    {
        return $value && ! preg_match('/[[:cntrl:]\\\\\\s]/', $value)
            && (preg_match('~^/(?!/)~', $value)
                || (preg_match('~^https?://~i', $value) && filter_var($value, FILTER_VALIDATE_URL)))
            ? $value : null;
    }

    public function save(?Banner $record, array $data): Banner
    {
        $record ? Gate::authorize('update', $record) : Gate::authorize('create', Banner::class);
        $attributes = Validator::make($data, [
            'texto' => ['nullable', 'string', 'max:255'],
            'enlace' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
            'orden' => ['required', 'integer', 'between:0,2147483647'],
            'imagen' => ['required'],
        ])->validate();
        if (filled($attributes['enlace'] ?? null) && ! self::safeLink($attributes['enlace'])) {
            throw ValidationException::withMessages(['data.enlace' => 'Usa una ruta interna o un enlace HTTP o HTTPS válido.']);
        }

        $media = new MediaChanges('catalog/banners');
        try {
            $attributes['imagen'] = $media->stage($attributes['imagen'], 'data.imagen');
            [$banner, $old] = DB::transaction(function () use ($record, $attributes, $media): array {
                $banner = $record ? Banner::query()->lockForUpdate()->findOrFail($record->id) : new Banner;
                $old = $banner->imagen;
                $media->verify($attributes['imagen'], [$old], 'data.imagen');
                $banner->fill($attributes)->save();

                return [$banner, $old];
            }, 3);
        } catch (Throwable $error) {
            $media->undo();
            throw $error;
        }
        if ($old !== $banner->imagen) {
            $media->delete([$old]);
        }

        return $banner;
    }

    public function delete(Banner $record): bool
    {
        Gate::authorize('delete', $record);

        $path = DB::transaction(function () use ($record): string {
            $banner = Banner::query()->lockForUpdate()->findOrFail($record->id);
            $banner->delete();

            return $banner->imagen;
        });
        (new MediaChanges('catalog/banners'))->delete([$path]);

        return true;
    }
}
