<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/** data: URL veya ikili resmi dogrulayip public diske yazar (SVG kabul edilmez: betik tasiyabilir). */
class CourseImageStore
{
    public const MAX_BYTES = 5_000_000;
    public const MAX_TOTAL = 60_000_000;
    private const TYPES = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];

    private int $total = 0;
    /** @var list<string> bu ice aktarma sirasinda yazilan yollar (hata olursa silinebilsin) */
    public array $written = [];
    /** @var array<string,string> hash => url (ayni resim tekrar yazilmaz) */
    private array $seen = [];

    public function __construct(private string $dir) {}

    private const SVG_MAX = 400_000;
    private const SVG_DENY_TAGS = ['script', 'foreignobject', 'iframe', 'object', 'embed', 'audio', 'video', 'canvas', 'animate', 'set', 'animatetransform', 'animatemotion', 'handler', 'listener', 'a', 'switch'];

    /**
     * v1 uretici betikleri slaytlara SVG data URL koyuyor. SVG dosya olarak sunulursa kendi basina acildiginda
     * betik calistirabilir; bu yuzden dosyaya YAZILMAZ: katı dogrulamadan gecerse <img> icinde guvenli kalan
     * data: URL olarak saklanir (img icinde betik/dis kaynak calismaz, data: belgesi opak kokenlidir).
     */
    public function safeSvgDataUrl(string $dataUrl): ?string
    {
        if (! preg_match('#^data:image/svg\+xml(?:;charset=[A-Za-z0-9\-]+)?(;base64)?,(.*)$#is', trim($dataUrl), $m)) {
            return null;
        }
        $svg = $m[1] !== '' ? base64_decode(preg_replace('/\s+/', '', $m[2]), true) : rawurldecode($m[2]);
        if (! is_string($svg) || $svg === '' || strlen($svg) > self::SVG_MAX || $this->total + strlen($svg) > self::MAX_TOTAL) {
            return null;
        }
        if (preg_match('/<!DOCTYPE|<!ENTITY|<\?xml-stylesheet/i', $svg)) {
            return null;                                                  // XXE / dis stil sayfasi
        }
        $dom = new \DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (! $ok || ! $dom->documentElement || strtolower($dom->documentElement->localName) !== 'svg') {
            return null;
        }
        foreach ($dom->getElementsByTagName('*') as $el) {
            if (in_array(strtolower($el->localName), self::SVG_DENY_TAGS, true)) {
                return null;
            }
            if (strtolower($el->localName) === 'style' && preg_match('/@import|url\s*\(|expression/i', $el->textContent)) {
                return null;
            }
            foreach ($el->attributes as $attr) {
                $n = strtolower($attr->localName);
                $v = trim($attr->value);
                if (str_starts_with($n, 'on') || preg_match('/javascript:|vbscript:/i', $v)) {
                    return null;
                }
                if ($n === 'href' && ! preg_match('#^(\#|data:image/(png|jpe?g|gif|webp);base64,)#i', $v)) {
                    return null;                                          // dis kaynak yok
                }
                if ($n === 'style' && preg_match('/@import|url\s*\(\s*[\'"]?(?!#)|expression/i', $v)) {
                    return null;
                }
            }
        }
        $clean = $dom->saveXML($dom->documentElement);
        $this->total += strlen($clean);

        return 'data:image/svg+xml;base64,'.base64_encode($clean);
    }

    /** @return string|null genel URL; gecersizse null */
    public function fromDataUrl(string $dataUrl): ?string
    {
        if (preg_match('#^\s*data:image/svg\+xml#i', $dataUrl)) {
            return $this->safeSvgDataUrl($dataUrl);
        }
        if (! preg_match('#^data:image/(?:png|jpe?g|gif|webp);base64,([A-Za-z0-9+/=\s]+)$#i', trim($dataUrl), $m)) {
            return null;
        }
        $bin = base64_decode(preg_replace('/\s+/', '', $m[1]), true);

        return $bin === false ? null : $this->fromBinary($bin);
    }

    public function fromBinary(string $bin): ?string
    {
        if ($bin === '' || strlen($bin) > self::MAX_BYTES || $this->total + strlen($bin) > self::MAX_TOTAL) {
            return null;
        }
        $info = @getimagesizefromstring($bin);                       // iceriği gercekten resim mi (uzantiya/mime'a guvenme)
        if (! $info || ! isset(self::TYPES[$info[2]]) || $info[0] > 8000 || $info[1] > 8000) {
            return null;
        }
        $hash = sha1($bin);
        if (isset($this->seen[$hash])) {
            return $this->seen[$hash];
        }
        $path = trim($this->dir, '/').'/'.$hash.'.'.self::TYPES[$info[2]];
        Storage::disk('public')->put($path, $bin);
        $this->total += strlen($bin);
        $this->written[] = $path;

        return $this->seen[$hash] = Storage::disk('public')->url($path);
    }

    /** Yol (cover icin). */
    public function storeCover(string $bin): ?string
    {
        $url = $this->fromBinary($bin);

        return $url ? ltrim(parse_url($url, PHP_URL_PATH) ?? '', '/') : null;
    }

    public function rollback(): void
    {
        foreach ($this->written as $p) {
            Storage::disk('public')->delete($p);
        }
    }
}
