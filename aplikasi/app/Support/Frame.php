<?php

namespace App\Support;

/** Rasio/ukuran tampilan kotak video dan eBook. Responsif: lebar selalu 100%, tinggi ikut rasio. */
class Frame
{
    public const RATIOS = ['16:9' => [16, 9], '4:3' => [4, 3], '1:1' => [1, 1], '9:16' => [9, 16], '21:9' => [21, 9], 'custom' => null];

    public static function defaults(): array
    {
        return ['ratio' => '16:9', 'w' => 1920, 'h' => 1080];
    }

    /** [lebar, tinggi] sebagai perbandingan (bukan piksel asli, hanya rasio). */
    public static function dims(array $cfg): array
    {
        $c = array_merge(self::defaults(), $cfg);
        if (($c['ratio'] ?? '16:9') !== 'custom' && isset(self::RATIOS[$c['ratio']])) return self::RATIOS[$c['ratio']];
        return [max(1, (int) $c['w']), max(1, (int) $c['h'])];
    }

    /** Nilai siap pakai untuk CSS aspect-ratio. */
    public static function css(array $cfg): string
    {
        [$w, $h] = self::dims($cfg);
        return $w.' / '.$h;
    }
}
