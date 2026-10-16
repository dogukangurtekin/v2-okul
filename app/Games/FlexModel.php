<?php

namespace App\Games;

/**
 * Arı Bahçesi için küçük Flexbox modeli: 300×300 kutu, 60×60 sabit arılar, tek satır (nowrap), boşluk/kenar boşluğu yok.
 * Tarayıcıdaki gerçek yerleşimle aynı sonucu vermesi için YALNIZCA belirli değerleri destekler; desteklenmeyen ama
 * geçerli CSS değerleri (start, end, place-items...) hata olarak bildirilir, sessizce yok sayılmaz.
 */
class FlexModel
{
    public const BOX = 300;
    public const BEE = 60;

    private const JUSTIFY = ['flex-start', 'flex-end', 'center', 'space-between', 'space-around', 'space-evenly', 'normal'];
    private const ALIGN = ['flex-start', 'flex-end', 'center', 'normal', 'stretch', 'baseline'];
    private const DIRECTIONS = ['row', 'row-reverse', 'column', 'column-reverse'];
    /** Geçerli CSS olup bu modelin doğru hesaplayamadığı değerler */
    private const UNSUPPORTED_JUSTIFY = ['start', 'end', 'left', 'right', 'stretch', 'safe', 'unsafe'];
    private const UNSUPPORTED_ALIGN = ['start', 'end', 'self-start', 'self-end', 'left', 'right', 'safe', 'unsafe'];
    private const RESET = ['initial', 'unset', 'revert', 'revert-layer', 'inherit'];

    /**
     * #garden bildirimlerinden (sıralı: [prop, value, important]) yerleşim hesaplar.
     *
     * @param  list<array{0:string,1:string,2:bool}>  $decls
     * @return array{error:?string, positions:list<array{0:float,1:float}>}
     */
    public static function layout(array $decls, int $bees): array
    {
        $eff = self::cascade($decls, $error);
        if ($error) {
            return ['error' => $error, 'positions' => []];
        }

        $display = 'block';
        if (isset($eff['display']) && ! in_array($eff['display'], self::RESET, true)) {
            if (in_array($eff['display'], ['flex', 'inline-flex'], true)) {
                $display = 'flex';
            } elseif ($eff['display'] === 'block') {
                $display = 'block';
            } else {
                return ['error' => 'Bu atölyede yalnızca display: flex (veya block) kullanılır.', 'positions' => []];
            }
        }
        if ($display === 'block') {                                    // bloklar alt alta dizilir
            return ['error' => null, 'positions' => array_map(fn ($i) => [0.0, (float) ($i * self::BEE)], range(0, $bees - 1))];
        }

        $direction = 'row';
        $justify = 'flex-start';
        $align = 'flex-start';

        if (isset($eff['flex-direction']) && ! in_array($eff['flex-direction'], self::RESET, true)) {
            $direction = $eff['flex-direction'];
        }
        if (isset($eff['justify-content']) && ! in_array($eff['justify-content'], self::RESET, true)) {
            $justify = $eff['justify-content'] === 'normal' ? 'flex-start' : $eff['justify-content'];
        }
        if (isset($eff['align-items']) && ! in_array($eff['align-items'], self::RESET, true)) {
            $align = in_array($eff['align-items'], ['normal', 'stretch', 'baseline'], true) ? 'flex-start' : $eff['align-items'];
        }

        // Ana eksen: ilk elemandan son elemana ofsetler (flex-start kenarından)
        $free = self::BOX - $bees * self::BEE;
        $pos = [];
        for ($i = 0; $i < $bees; $i++) {
            $pos[$i] = match ($justify) {
                'flex-end' => $free + $i * self::BEE,
                'center' => $free / 2 + $i * self::BEE,
                'space-between' => $bees > 1 ? $i * ($free / ($bees - 1)) + $i * self::BEE : 0,
                'space-around' => ($free / $bees) / 2 + $i * ($free / $bees) + $i * self::BEE,
                'space-evenly' => ($free / ($bees + 1)) * ($i + 1) + $i * self::BEE,
                default => $i * self::BEE,
            };
        }
        $cross = match ($align) {
            'flex-end' => self::BOX - self::BEE,
            'center' => (self::BOX - self::BEE) / 2,
            default => 0,
        };

        $out = [];
        foreach ($pos as $p) {
            $main = in_array($direction, ['row-reverse', 'column-reverse'], true) ? self::BOX - $p - self::BEE : $p;
            $out[] = in_array($direction, ['row', 'row-reverse'], true) ? [(float) $main, (float) $cross] : [(float) $cross, (float) $main];
        }

        return ['error' => null, 'positions' => $out];
    }

    /** flex-flow: [direction] [wrap] (herhangi sırada). Geçerliyse yön (belirtilmemişse 'row'), değilse null. */
    private static function flowDirection(string $value): ?string
    {
        if (in_array($value, ['initial', 'unset', 'revert', 'revert-layer', 'inherit'], true)) {
            return 'row';
        }
        $dir = null;
        $wrap = false;
        $tokens = preg_split('/\s+/', trim($value));
        if (count($tokens) > 2) {
            return null;
        }
        foreach ($tokens as $t) {
            if (in_array($t, self::DIRECTIONS, true) && $dir === null) {
                $dir = $t;
            } elseif (in_array($t, ['nowrap', 'wrap', 'wrap-reverse'], true) && ! $wrap) {
                $wrap = true;
            } else {
                return null;
            }
        }

        return $dir ?? 'row';
    }

    /**
     * Basamaklı değer: geçersiz değerler CSS gibi YOK sayılır (önceki geçerli değer kalır), !important kazanır,
     * ama desteklenmeyen-geçerli değerler hata verir.
     *
     * @return array<string,string>
     */
    private static function cascade(array $decls, ?string &$error): array
    {
        $error = null;
        $eff = [];
        $imp = [];
        foreach ($decls as [$prop, $value, $important]) {
            if (str_contains($value, 'var(') || str_contains($value, 'calc(') || str_contains($value, 'env(')) {
                $error = "“{$prop}” içinde var()/calc() kullanılamaz.";

                return [];
            }
            if (in_array($prop, ['place-items', 'place-content'], true)) {
                $error = "{$prop} kısayolu bu atölyede kullanılmaz; justify-content ve align-items yazın.";

                return [];
            }
            if ($prop === 'flex-flow') {                               // kısayol: sıraya duyarlı olması için uzun forma açılır
                $dir = self::flowDirection($value);
                if ($dir === null) {
                    continue;                                          // geçersiz kısayol: tarayıcı yok sayar
                }
                $prop = 'flex-direction';
                $value = $dir;
            }
            $valid = match ($prop) {
                'display' => preg_match('/^[a-z\-]+( [a-z\-]+)?$/', $value) === 1,
                'flex-direction' => in_array($value, [...self::DIRECTIONS, ...self::RESET], true),
                'justify-content' => in_array($value, [...self::JUSTIFY, ...self::UNSUPPORTED_JUSTIFY, ...self::RESET], true),
                'align-items' => in_array($value, [...self::ALIGN, ...self::UNSUPPORTED_ALIGN, 'first baseline', 'last baseline', ...self::RESET], true),
                default => null,
            };
            if ($valid === null) {
                continue;                                              // modeli etkilemeyen özellik (önizlemede zorla sıfırlanır)
            }
            if (! $valid) {
                continue;                                              // geçersiz değer: tarayıcı yok sayar
            }
            if ($prop === 'justify-content' && in_array($value, self::UNSUPPORTED_JUSTIFY, true)) {
                $error = "justify-content: {$value} bu atölyede desteklenmiyor; flex-start, flex-end, center veya space-* kullanın.";

                return [];
            }
            if ($prop === 'align-items' && (in_array($value, self::UNSUPPORTED_ALIGN, true) || str_contains($value, 'baseline') && str_contains($value, ' '))) {
                $error = "align-items: {$value} bu atölyede desteklenmiyor; flex-start, flex-end veya center kullanın.";

                return [];
            }
            if (($imp[$prop] ?? false) && ! $important) {
                continue;                                              // önceki !important daha güçlü
            }
            $eff[$prop] = $value;
            $imp[$prop] = $important || ($imp[$prop] ?? false);
        }

        return $eff;
    }
}
