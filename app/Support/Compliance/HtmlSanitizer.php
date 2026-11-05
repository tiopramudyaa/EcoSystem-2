<?php

namespace App\Support\Compliance;

use DOMDocument;
use DOMElement;

/**
 * Cleans the HTML of a compliance article before it is stored: only a short list of formatting tags survive, every attribute is
 * removed except a safe http(s)/mailto `href` on links. Articles are written by staff with the Compliance permission, but the text is
 * shown to everyone with the portal, so it is never trusted as-is.
 */
class HtmlSanitizer
{
    private const ALLOWED = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h3', 'h4', 'blockquote', 's', 'strike', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $stripped = strip_tags($html, array_map(fn ($t) => "<{$t}>", self::ALLOWED));

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $stripped . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $doc->getElementById('root');
        if (!$root) {
            return e(strip_tags($html));
        }

        foreach ($root->getElementsByTagName('*') as $el) {
            /** @var DOMElement $el */
            foreach (iterator_to_array($el->attributes) as $attr) {
                $keep = $el->tagName === 'a' && $attr->name === 'href' && preg_match('#^(https?://|mailto:)#i', trim($attr->value));
                if (!$keep) {
                    $el->removeAttributeNode($attr);
                }
            }
            if ($el->tagName === 'a') {
                $el->setAttribute('rel', 'noopener noreferrer');
                $el->setAttribute('target', '_blank');
            }
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }
}
