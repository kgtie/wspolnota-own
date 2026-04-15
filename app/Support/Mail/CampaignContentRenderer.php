<?php

namespace App\Support\Mail;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

class CampaignContentRenderer
{
    /**
     * @var array<string, string>
     */
    private const TAG_STYLES = [
        'h1' => 'margin:0 0 14px;font-family:Georgia,Times New Roman,serif;font-size:32px;line-height:1.15;color:#1c1917;font-weight:700;',
        'h2' => 'margin:22px 0 12px;font-family:Georgia,Times New Roman,serif;font-size:26px;line-height:1.2;color:#1c1917;font-weight:700;',
        'h3' => 'margin:18px 0 10px;font-family:Arial,Helvetica,sans-serif;font-size:20px;line-height:1.35;color:#1f2937;font-weight:700;',
        'p' => 'margin:0 0 14px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#475569;',
        'ul' => 'margin:0 0 16px 22px;padding:0;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#475569;',
        'ol' => 'margin:0 0 16px 22px;padding:0;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#475569;',
        'li' => 'margin:0 0 8px;',
        'blockquote' => 'margin:0 0 18px;padding:14px 16px;border-left:4px solid #b87333;background-color:#f8f3ec;color:#57534e;font-style:italic;',
        'a' => 'color:#b87333;text-decoration:underline;',
        'img' => 'display:block;max-width:100%;height:auto;border:0;border-radius:18px;margin:16px 0;',
        'hr' => 'margin:18px 0;border:0;border-top:1px solid #eadfce;',
        'table' => 'width:100%;margin:0 0 16px;border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.7;color:#334155;',
        'thead' => '',
        'tbody' => '',
        'tr' => '',
        'th' => 'padding:10px 12px;border:1px solid #eadfce;background-color:#f8f3ec;text-align:left;font-weight:700;',
        'td' => 'padding:10px 12px;border:1px solid #eadfce;vertical-align:top;',
        'strong' => 'font-weight:700;',
        'b' => 'font-weight:700;',
        'em' => 'font-style:italic;',
        'i' => 'font-style:italic;',
        'u' => 'text-decoration:underline;',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'target', 'rel', 'title'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'td' => ['colspan', 'rowspan', 'align'],
        'th' => ['colspan', 'rowspan', 'align'],
        'table' => ['width', 'align'],
    ];

    /**
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'a',
        'b',
        'blockquote',
        'br',
        'em',
        'h1',
        'h2',
        'h3',
        'hr',
        'i',
        'img',
        'li',
        'ol',
        'p',
        'strong',
        'table',
        'tbody',
        'td',
        'th',
        'thead',
        'tr',
        'u',
        'ul',
    ];

    public function renderForEmail(?string $html): string
    {
        $sanitized = $this->sanitize($html);

        if ($sanitized === '') {
            return '';
        }

        $document = $this->createDocument($sanitized);

        if (! $document) {
            return '';
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return '';
        }

        $this->walk($body);

        return trim($this->innerHtml($body));
    }

    public function sanitize(?string $html): string
    {
        $normalized = trim((string) $html);

        if ($normalized === '' || $normalized === '<p><br></p>') {
            return '';
        }

        $document = $this->createDocument($normalized);

        if (! $document) {
            return '';
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return '';
        }

        $this->sanitizeNode($body);

        return trim($this->innerHtml($body));
    }

    public function toPlainText(?string $html): string
    {
        $normalized = $this->sanitize($html);

        if ($normalized === '') {
            return '';
        }

        $withLineBreaks = preg_replace('/<(br|\/p|\/div|\/li|\/tr|\/h[1-3])[^>]*>/i', "\n", $normalized) ?? $normalized;
        $withBlocks = preg_replace('/<(p|div|li|tr|h[1-3]|ul|ol|table|blockquote)[^>]*>/i', "\n", $withLineBreaks) ?? $withLineBreaks;
        $text = html_entity_decode(strip_tags($withBlocks), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return (string) Str::of($text)
            ->replace("\r", '')
            ->replaceMatches("/\n{3,}/", "\n\n")
            ->trim();
    }

    private function sanitizeNode(DOMNode $node): void
    {
        if ($node instanceof DOMElement) {
            $tagName = strtolower($node->tagName);

            if (in_array($tagName, ['html', 'body'], true)) {
                $children = [];

                foreach ($node->childNodes as $child) {
                    $children[] = $child;
                }

                foreach ($children as $child) {
                    $this->sanitizeNode($child);
                }

                return;
            }

            if ($tagName === 'div') {
                $this->replaceTag($node, 'p');

                return;
            }

            if (! in_array($tagName, self::ALLOWED_TAGS, true)) {
                $this->unwrapNode($node);

                return;
            }

            $this->filterAttributes($node, $tagName);
        }

        $children = [];

        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            $this->sanitizeNode($child);
        }
    }

    private function walk(DOMNode $node): void
    {
        if ($node instanceof DOMElement) {
            $tagName = strtolower($node->tagName);
            $existingStyle = trim((string) $node->getAttribute('style'));
            $resolvedStyle = trim(($existingStyle !== '' ? $existingStyle.';' : '').(self::TAG_STYLES[$tagName] ?? ''));

            if ($resolvedStyle !== '') {
                $node->setAttribute('style', $resolvedStyle);
            }

            if ($tagName === 'a') {
                if ($node->getAttribute('target') === '') {
                    $node->setAttribute('target', '_blank');
                }

                if ($node->getAttribute('rel') === '') {
                    $node->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }

        foreach ($node->childNodes as $child) {
            $this->walk($child);
        }
    }

    private function filterAttributes(DOMElement $element, string $tagName): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tagName] ?? [];
        $toRemove = [];

        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (! in_array($name, $allowed, true)) {
                $toRemove[] = $name;

                continue;
            }

            $value = trim((string) $attribute->nodeValue);

            if (in_array($name, ['href', 'src'], true)) {
                $normalized = $this->normalizeUrl($value);

                if ($normalized === null) {
                    $toRemove[] = $name;

                    continue;
                }

                $element->setAttribute($name, $normalized);
            }
        }

        foreach ($toRemove as $name) {
            $element->removeAttribute($name);
        }
    }

    private function normalizeUrl(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://', 'mailto:', 'tel:'])) {
            return $url;
        }

        if (Str::startsWith($url, '//')) {
            return 'https:'.$url;
        }

        if (Str::startsWith($url, '/')) {
            return url($url);
        }

        return null;
    }

    private function replaceTag(DOMElement $element, string $tag): void
    {
        $document = $element->ownerDocument;

        if (! $document || ! $element->parentNode) {
            return;
        }

        $replacement = $document->createElement($tag);

        while ($element->firstChild) {
            $replacement->appendChild($element->firstChild);
        }

        $element->parentNode->replaceChild($replacement, $element);
        $this->sanitizeNode($replacement);
    }

    private function unwrapNode(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private function createDocument(string $html): ?DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);
        $wrappedHtml = <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>{$html}</body>
</html>
HTML;
        $loaded = $document->loadHTML(
            $wrappedHtml,
            LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $loaded ? $document : null;
    }

    private function innerHtml(DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument?->saveHTML($child) ?? '';
        }

        return $html;
    }
}
