<?php

namespace App\Support\Mail;

use App\Models\Parish;

class EmailComposer
{
    public function __construct(private readonly EmailThemeFactory $themeFactory) {}

    public function composeView(
        string $htmlBodyView,
        string $textBodyView,
        array $bodyData = [],
        ?Parish $parish = null,
        array $context = [],
    ): array {
        return [
            'theme' => $this->themeFactory->make($parish, $context),
            'preheader' => (string) ($context['preheader'] ?? ''),
            'body_html' => view($htmlBodyView, $bodyData)->render(),
            'body_text' => trim(view($textBodyView, $bodyData)->render()),
        ];
    }
}
