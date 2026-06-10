<?php

namespace App\Support;

use DOMDocument;
use DOMElement;

/**
 * Allowlist sanitizer for the RichEditor HTML we render unescaped (recipe
 * descriptions — the only raw-HTML sink in the app). Keeps a small set of
 * formatting tags, drops every attribute except validated hrefs on <a>, and
 * removes everything else. Defense in depth: today the input is admin-only,
 * but a single compromised admin session must not become stored XSS.
 */
final class RichText
{
    /**
     * Tags the Filament RichEditor can produce. Anything else is unwrapped
     * (kept as text) or, for script/style, removed with its content.
     *
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 's', 'u', 'a',
        'ul', 'ol', 'li', 'h2', 'h3', 'blockquote', 'code', 'pre',
    ];

    /** @var list<string> */
    private const SAFE_SCHEMES = ['http', 'https', 'mailto'];

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        // Wrap for a single root; numeric entities preserve UTF-8 (Cyrillic)
        // regardless of how libxml would otherwise guess the encoding.
        $dom->loadHTML(
            '<div>'.mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8').'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementsByTagName('div')->item(0);

        if (! $root instanceof DOMElement) {
            return '';
        }

        foreach (iterator_to_array($dom->getElementsByTagName('*')) as $element) {
            if ($element === $root) {
                continue;
            }

            $tag = strtolower($element->nodeName);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::dropElement($element, removeContent: in_array($tag, ['script', 'style'], true));

                continue;
            }

            self::cleanAttributes($element);
        }

        $out = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= (string) $dom->saveHTML($child);
        }

        return trim($out);
    }

    /**
     * Remove a disallowed element. script/style are dropped wholesale; other
     * tags are unwrapped so their (already-sanitized) text content survives.
     */
    private static function dropElement(DOMElement $element, bool $removeContent): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        if (! $removeContent) {
            while ($element->firstChild !== null) {
                $parent->insertBefore($element->firstChild, $element);
            }
        }

        $parent->removeChild($element);
    }

    private static function cleanAttributes(DOMElement $element): void
    {
        $tag = strtolower($element->nodeName);

        // Snapshot names first — removing while iterating the live map skips entries.
        $names = [];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $names[] = $attribute->nodeName;
        }

        foreach ($names as $name) {
            $keep = $tag === 'a'
                && strtolower($name) === 'href'
                && self::isSafeHref((string) $element->getAttribute($name));

            if (! $keep) {
                $element->removeAttribute($name);
            }
        }

        if ($tag === 'a' && $element->hasAttribute('href')) {
            $element->setAttribute('rel', 'nofollow noopener noreferrer');
            $element->setAttribute('target', '_blank');
        }
    }

    private static function isSafeHref(string $href): bool
    {
        $href = trim($href);

        if ($href === '') {
            return false;
        }

        if (str_starts_with($href, '#') || str_starts_with($href, '/')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));

        return in_array($scheme, self::SAFE_SCHEMES, true);
    }
}
