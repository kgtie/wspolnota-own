<?php

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns\CanBeLengthConstrained;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Contracts\CanBeLengthConstrained as CanBeLengthConstrainedContract;
use Filament\Forms\Components\Field;

class QuillEditor extends Field implements CanBeLengthConstrainedContract
{
    use CanBeLengthConstrained;
    use HasPlaceholder;

    /**
     * @var view-string
     */
    protected string $view = 'forms.components.quill-editor';

    /**
     * @var array<array<int, string>> | Closure | null
     */
    protected array|Closure|null $toolbar = null;

    protected int|Closure|null $minHeight = null;

    protected string|Closure|null $imageUploadUrl = null;

    protected int|Closure|null $maxUploadSize = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default('');

        $this->dehydrateStateUsing(static function ($state): string {
            $normalized = trim((string) ($state ?? ''));

            if (($normalized === '') || ($normalized === '<p><br></p>')) {
                return '';
            }

            return $normalized;
        });
    }

    /**
     * @param  array<array<int, string>> | Closure | null  $toolbar
     */
    public function toolbar(array|Closure|null $toolbar): static
    {
        $this->toolbar = $toolbar;

        return $this;
    }

    public function minHeight(int|Closure|null $height): static
    {
        $this->minHeight = $height;

        return $this;
    }

    public function imageUploadUrl(string|Closure|null $url): static
    {
        $this->imageUploadUrl = $url;

        return $this;
    }

    public function maxUploadSize(int|Closure|null $kilobytes): static
    {
        $this->maxUploadSize = $kilobytes;

        return $this;
    }

    /**
     * @return array<array<int, string>>
     */
    public function getToolbar(): array
    {
        return $this->evaluate($this->toolbar) ?? [
            ['header-1', 'header-2'],
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote'],
            ['link', 'image'],
            ['list-ordered', 'list-bullet'],
            ['clean'],
        ];
    }

    public function getMinHeight(): int
    {
        return (int) ($this->evaluate($this->minHeight) ?? 320);
    }

    public function getImageUploadUrl(): ?string
    {
        return $this->evaluate($this->imageUploadUrl);
    }

    public function getMaxUploadSize(): int
    {
        return (int) ($this->evaluate($this->maxUploadSize) ?? 8192);
    }
}
