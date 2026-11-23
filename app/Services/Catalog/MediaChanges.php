<?php

namespace App\Services\Catalog;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

final class MediaChanges
{
    private array $created = [];

    public function __construct(private string $directory) {}

    public function stage(mixed $value, string $field): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        if (! $value instanceof UploadedFile) {
            throw ValidationException::withMessages([$field => 'La imagen seleccionada no es válida.']);
        }

        $validation = Validator::make(['image' => $value], [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
        ]);
        if ($validation->fails()) {
            throw ValidationException::withMessages([$field => $validation->errors()->get('image')]);
        }

        try {
            $path = $this->disk()->putFile($this->directory, $value);
        } catch (Throwable) {
            $path = false;
        }

        if (! $path) {
            throw ValidationException::withMessages([$field => 'No se pudo guardar la imagen. Inténtalo de nuevo.']);
        }

        $this->created[] = $path;

        return $path;
    }

    public function verify(?string $path, array $existing, string $field): void
    {
        if ($path !== null && ! in_array($path, [...$existing, ...$this->created], true)) {
            throw ValidationException::withMessages([$field => 'La imagen no pertenece a este registro.']);
        }
    }

    public function undo(): void
    {
        $this->delete($this->created);
    }

    public function delete(array $paths): void
    {
        $paths = array_filter($paths, fn ($path) => is_string($path) && str_starts_with($path, $this->directory.'/'));
        if (! $paths) {
            return;
        }

        try {
            if (! $this->disk()->delete(array_values($paths))) {
                Log::warning('No se pudieron limpiar todos los archivos de catálogo.');
            }
        } catch (Throwable) {
            Log::warning('No se pudieron limpiar todos los archivos de catálogo.');
        }
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('zaza.catalog_disk'));
    }
}
