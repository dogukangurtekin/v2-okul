<?php

namespace App\Support;

use RuntimeException;

/** Bagimliliksiz minimal .xlsx okuyucu: ilk sayfayi satir/sutun dizisi olarak dondurur (ext-zip gerekir). */
class XlsxReader
{
    private const MAX_UNCOMPRESSED = 20_000_000;

    /** @return list<list<string>> */
    public static function read(string $path): array
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new RuntimeException('Sunucuda zip eklentisi yok; dosyayı CSV olarak kaydedip yükleyin.');
        }
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Geçersiz Excel dosyası.');
        }
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $total += $zip->statIndex($i)['size'];
        }
        if ($total > self::MAX_UNCOMPRESSED) {
            throw new RuntimeException('Excel dosyası çok büyük.');
        }

        $strings = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sx = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);
            foreach ($sx->si ?? [] as $si) {
                $text = '';
                foreach ($si->xpath('.//*[local-name()="t"]') as $t) {
                    $text .= (string) $t;
                }
                $strings[] = $text;
            }
        }

        $sheetName = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $n = $zip->getNameIndex($i);
            if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $n) && ($sheetName === null || strnatcmp($n, $sheetName) < 0)) {
                $sheetName = $n;
            }
        }
        $sheetXml = $sheetName ? $zip->getFromName($sheetName) : false;
        $zip->close();
        if ($sheetXml === false) {
            throw new RuntimeException('Excel sayfası okunamadı.');
        }

        $sheet = simplexml_load_string($sheetXml, \SimpleXMLElement::class, LIBXML_NONET);
        $rows = [];
        foreach ($sheet->sheetData->row ?? [] as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $col = self::columnIndex((string) $c['r']);
                $type = (string) $c['t'];
                $value = match ($type) {
                    's' => $strings[(int) $c->v] ?? '',
                    'inlineStr' => (string) ($c->is->t ?? ''),
                    default => (string) $c->v,
                };
                if ($type === '' && is_numeric($value) && str_contains($value, '.') === false) {
                    $value = (string) $value;
                } elseif ($type === '' && is_numeric($value) && floor((float) $value) == (float) $value) {
                    $value = (string) (int) $value; // 5.0 -> 5
                }
                $cells[$col] = trim($value);
            }
            $max = $cells ? max(array_keys($cells)) : -1;
            $line = [];
            for ($i = 0; $i <= $max; $i++) {
                $line[] = $cells[$i] ?? '';
            }
            $rows[] = $line;
        }

        return $rows;
    }

    private static function columnIndex(string $ref): int
    {
        preg_match('/^([A-Z]+)/', $ref, $m);
        $n = 0;
        foreach (str_split($m[1] ?? 'A') as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n - 1;
    }
}
