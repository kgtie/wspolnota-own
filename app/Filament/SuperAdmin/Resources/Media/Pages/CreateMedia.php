<?php

namespace App\Filament\SuperAdmin\Resources\Media\Pages;

use App\Filament\SuperAdmin\Resources\Media\MediaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Rzeczywiste tworzenie wpisu media odbywa sie przez model docelowy.
 *
 * Spatie Media Library zapisuje plik wzgledem rekordu wlasciciela, dlatego
 * strona waliduje model, rekord i kolekcje zanim utworzy obiekt Media.
 */
class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    /**
     * @param  array<string,mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $modelType = (string) ($data['model_type'] ?? '');
        $modelId = (int) ($data['model_id'] ?? 0);

        if (! array_key_exists($modelType, MediaResource::getAttachableModelOptions())) {
            throw ValidationException::withMessages([
                'model_type' => 'Nieobslugiwany model docelowy.',
            ]);
        }

        if (! class_exists($modelType)) {
            throw ValidationException::withMessages([
                'model_type' => 'Klasa modelu nie istnieje.',
            ]);
        }

        /** @var \Illuminate\Database\Eloquent\Model|null $targetRecord */
        $targetRecord = $modelType::query()->find($modelId);

        if (! $targetRecord instanceof Model) {
            throw ValidationException::withMessages([
                'model_id' => 'Nie znaleziono rekordu docelowego.',
            ]);
        }

        if (! method_exists($targetRecord, 'addMedia')) {
            throw ValidationException::withMessages([
                'model_type' => 'Wybrany model nie obsluguje mediow.',
            ]);
        }

        // Filament moze zwrocic pojedynczy plik lub jednopunktowa tablice;
        // normalizujemy ten stan zanim przekażemy go do Media Library.
        $uploaded = $this->resolveUploadedFile($data['uploaded_file'] ?? null);

        $mediaAdder = $targetRecord->addMedia($uploaded);

        if (filled($data['name'] ?? null)) {
            $mediaAdder->usingName((string) $data['name']);
        }

        if (filled($data['file_name'] ?? null)) {
            $mediaAdder->usingFileName((string) $data['file_name']);
        }

        if (is_array($data['custom_properties'] ?? null) && $data['custom_properties'] !== []) {
            $mediaAdder->withCustomProperties($data['custom_properties']);
        }

        $collectionName = filled($data['collection_name'] ?? null)
            ? (string) $data['collection_name']
            : 'default';

        $disk = filled($data['disk'] ?? null)
            ? (string) $data['disk']
            : null;

        $media = $disk
            ? $mediaAdder->toMediaCollection($collectionName, $disk)
            : $mediaAdder->toMediaCollection($collectionName);

        if (! filled($media->name)) {
            $media->name = pathinfo((string) $media->file_name, PATHINFO_FILENAME);
        }

        if (! filled($media->file_name)) {
            $media->file_name = Str::random(16);
        }

        $media->save();

        return $media;
    }

    protected function resolveUploadedFile(mixed $uploaded): UploadedFile|string
    {
        if (is_array($uploaded)) {
            $uploaded = reset($uploaded);
        }

        if ($uploaded instanceof UploadedFile || is_string($uploaded)) {
            return $uploaded;
        }

        throw ValidationException::withMessages([
            'uploaded_file' => 'Nieprawidlowy plik wejsciowy.',
        ]);
    }
}
