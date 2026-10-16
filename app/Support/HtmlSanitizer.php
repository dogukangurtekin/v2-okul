<?php

namespace App\Support;

/**
 * Beyaz listeli HTML temizleyici (ders icerikleri icin). Betik, iframe, form, olay ozellikleri (onclick...),
 * javascript:/data: baglantilari ve riskli CSS atilir; bilinmeyen etiketler acilip yalniz metni korunur.
 */
class HtmlSanitizer
{
    private const ALLOWED = ['a', 'b', 'strong', 'i', 'em', 'u', 's', 'br', 'p', 'div', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'code', 'pre', 'hr', 'img', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'sub', 'sup', 'small', 'mark', 'figure', 'figcaption'];
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta', 'title', 'head', 'noscript', 'template', 'video', 'audio', 'canvas', 'frame', 'frameset', 'applet', 'base'];

    /** @param null|callable(string):?string $imageResolver img src'yi guvenli adrese cevirir; null donerse resim atilir */
    public function __construct(private $imageResolver = null) {}

    public function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        $dom = new \DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementById('__root') ?? $dom->getElementsByTagName('div')->item(0);
        if (! $root) {
            return '';
        }
        $this->walk($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    /** Okunur duz metin (blok sonlari satir sonu olur). */
    public static function toText(string $html): string
    {
        $t = preg_replace('#</(p|div|h[1-6]|li|tr|blockquote|pre)>|<br\s*/?>#i', "\n", $html);
        $t = html_entity_decode(strip_tags((string) $t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace("/[ \t\x{00A0}]+/u", ' ', $t);
        $t = preg_replace("/\n[ ]*/", "\n", (string) $t);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", (string) $t));
    }

    private function walk(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment || $child instanceof \DOMProcessingInstruction || $child instanceof \DOMCdataSection) {
                $node->removeChild($child);
                continue;
            }
            if (! $child instanceof \DOMElement) {
                continue;                                   // metin dugumu
            }
            $tag = strtolower($child->nodeName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }
            $this->walk($child);                            // once cocuklar

            if (! in_array($tag, self::ALLOWED, true)) {
                while ($child->firstChild) {                // etiketi ac, icerigi koru
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            if ($tag === 'img' && ! $this->cleanImage($child)) {
                $node->removeChild($child);
                continue;
            }
            $this->cleanAttributes($child, $tag);
        }
    }

    private function cleanImage(\DOMElement $img): bool
    {
        $src = trim($img->getAttribute('src'));
        $resolved = $this->imageResolver
            ? ($this->imageResolver)($src)
            : (preg_match('#^https?://#i', $src) ? $src : null);

        if (! $resolved) {
            return false;
        }
        $img->setAttribute('src', $resolved);

        return true;
    }

    private function cleanAttributes(\DOMElement $el, string $tag): void
    {
        $keep = [];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $val = trim($attr->nodeValue);

            if ($tag === 'a' && $name === 'href' && preg_match('#^(https?://|mailto:)#i', $val)) {
                $keep['href'] = $val;
            } elseif ($tag === 'img' && $name === 'src') {
                $keep['src'] = $val;
            } elseif ($tag === 'img' && $name === 'alt') {
                $keep['alt'] = mb_substr($val, 0, 200);
            } elseif ($tag === 'img' && in_array($name, ['width', 'height'], true) && preg_match('/^\d{1,4}$/', $val)) {
                $keep[$name] = $val;
            } elseif (in_array($tag, ['td', 'th'], true) && in_array($name, ['colspan', 'rowspan'], true) && preg_match('/^\d{1,2}$/', $val)) {
                $keep[$name] = $val;
            } elseif ($name === 'style' && ($style = $this->cleanStyle($val)) !== '') {
                $keep['style'] = $style;
            }
        }
        foreach (iterator_to_array($el->attributes) as $attr) {
            $el->removeAttributeNode($attr);
        }
        foreach ($keep as $k => $v) {
            $el->setAttribute($k, $v);
        }
        if ($tag === 'a' && isset($keep['href'])) {
            $el->setAttribute('target', '_blank');
            $el->setAttribute('rel', 'noopener noreferrer');
        }
        if ($tag === 'img') {
            $el->setAttribute('loading', 'lazy');
        }
    }

    private function cleanStyle(string $style): string
    {
        $color = '(#[0-9a-fA-F]{3,8}|rgba?\(\s*[\d.\s,%]+\)|[a-zA-Z]{3,20})';
        $rules = [
            'color' => "/^$color$/", 'background-color' => "/^$color$/",
            'text-align' => '/^(left|right|center|justify)$/', 'font-weight' => '/^(normal|bold|[1-9]00)$/',
            'font-style' => '/^(normal|italic)$/', 'text-decoration' => '/^(none|underline|line-through)$/',
            'font-size' => '/^\d{1,3}(\.\d+)?(px|em|rem|%)$/',
        ];
        $out = [];
        foreach (explode(';', $style) as $decl) {
            if (! str_contains($decl, ':')) {
                continue;
            }
            [$p, $v] = array_map('trim', explode(':', $decl, 2));
            $p = strtolower($p);
            if (isset($rules[$p]) && preg_match($rules[$p], $v) && ! preg_match('/expression|url\(|javascript|@import/i', $v)) {
                $out[] = "$p:$v";
            }
        }

        return implode(';', $out);
    }
}
