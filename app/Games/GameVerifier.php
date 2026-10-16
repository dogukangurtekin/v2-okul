<?php

namespace App\Games;

/**
 * Sunucu tarafi cozum dogrulayici. Istemci "bitirdim" demez, cozumunu gonderir;
 * XP yalniz dogrulama gecerse verilir (cevap anahtari tarayiciya hic gitmez).
 */
interface GameVerifier
{
    /** @return array{ok: bool, messages: list<string>, score: int, explain?: ?string} */
    public function verify(int $level, array $payload): array;

    /** Istemciye gonderilecek seviye verisi (cevaplar/kontroller cikarilmis). */
    public function publicLevels(array $levels): array;
}
