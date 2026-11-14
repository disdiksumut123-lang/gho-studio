<?php

namespace App\Support;

/** Ukuran kertas dan huruf untuk dokumen output. */
class Page
{
    public const SIZES = ['A4' => [210, 297], 'A5' => [148, 210], 'F4' => [215, 330], 'Letter' => [216, 279], 'Legal' => [216, 356], 'custom' => null];
    public const FONTS = ['Times New Roman' => '"Times New Roman", Times, serif', 'Arial' => 'Arial, Helvetica, sans-serif', 'Calibri' => 'Calibri, Carlito, sans-serif', 'Cambria' => 'Cambria, Caladea, serif',
        'Georgia' => 'Georgia, serif', 'Bookman Old Style' => '"Bookman Old Style", serif', 'Tahoma' => 'Tahoma, Verdana, sans-serif', 'Plus Jakarta Sans' => '"Plus Jakarta Sans", system-ui, sans-serif'];
    public const FIELD_TYPES = [
        'text' => 'Teks pendek', 'textarea' => 'Paragraf (editor lengkap)', 'number' => 'Angka', 'date' => 'Tanggal', 'time' => 'Waktu',
        'select' => 'Pilihan (daftar turun)', 'radio' => 'Pilihan (bulatan)', 'checkbox' => 'Centang banyak', 'linear_scale' => 'Skala linear',
        'table' => 'Tabel', 'file' => 'Unggah berkas', 'photo' => 'Unggah foto', 'url' => 'Tautan (URL)', 'whatsapp' => 'Nomor WhatsApp',
        'signature' => 'Tanda tangan', 'narasi' => 'Teks tampilan (bukan isian)',
    ];

    public static function defaults(): array
    {
        return ['size' => 'A4', 'orientation' => 'portrait', 'width_mm' => 210, 'height_mm' => 297, 'margin' => ['top' => 30, 'right' => 30, 'bottom' => 30, 'left' => 40]];
    }

    public static function fontDefaults(): array
    {
        return ['input_family' => 'Plus Jakarta Sans', 'input_size' => 14, 'output_family' => 'Times New Roman', 'output_size' => 12, 'heading_size' => 14, 'line_height' => 1.5];
    }

    /** [lebar, tinggi] dalam mm sesuai orientasi */
    public static function dims(array $page): array
    {
        $p = array_merge(self::defaults(), $page);
        [$w, $h] = self::SIZES[$p['size']] ?? [(int) $p['width_mm'] ?: 210, (int) $p['height_mm'] ?: 297];
        return ($p['orientation'] ?? 'portrait') === 'landscape' ? [$h, $w] : [$w, $h];
    }

    public static function font(string $name): string { return self::FONTS[$name] ?? self::FONTS['Times New Roman']; }
}
