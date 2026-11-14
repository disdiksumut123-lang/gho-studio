<?php

namespace App\Support;

use ZipArchive;

/** Pembaca sederhana untuk .xlsx (lembar pertama) dan .csv. Tanpa pustaka tambahan. */
class Sheet
{
    public static function read(string $path, string $ext): array
    {
        return strtolower($ext) === 'xlsx' ? self::xlsx($path) : self::csv($path);
    }

    private static function csv(string $path): array
    {
        $rows = []; $h = fopen($path, 'r');
        $first = fgets($h); rewind($h);
        $sep = substr_count((string) $first, ';') > substr_count((string) $first, ',') ? ';' : ',';
        while (($r = fgetcsv($h, 0, $sep)) !== false) $rows[] = array_map(fn ($v) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $v)), $r);
        fclose($h);
        return $rows;
    }

    private static function xlsx(string $path): array
    {
        $z = new ZipArchive();
        if ($z->open($path) !== true) return [];
        $shared = [];
        if (($ss = $z->getFromName('xl/sharedStrings.xml')) !== false) {
            $x = simplexml_load_string($ss, 'SimpleXMLElement', LIBXML_NONET);
            foreach ($x->si as $si) {
                $t = isset($si->t) ? (string) $si->t : '';
                foreach ($si->r ?? [] as $r) $t .= (string) $r->t;
                $shared[] = $t;
            }
        }
        $sheet = $z->getFromName('xl/worksheets/sheet1.xml');
        $z->close();
        if ($sheet === false) return [];
        $x = simplexml_load_string($sheet, 'SimpleXMLElement', LIBXML_NONET);
        $rows = [];
        foreach ($x->sheetData->row as $row) {
            $out = [];
            foreach ($row->c as $c) {
                preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
                $col = 0; foreach (str_split($m[1] ?? 'A') as $ch) $col = $col * 26 + (ord($ch) - 64);
                $type = (string) $c['t'];
                $v = $type === 's' ? ($shared[(int) $c->v] ?? '') : ($type === 'inlineStr' ? (string) $c->is->t : (string) $c->v);
                // NIP panjang sering tersimpan sebagai angka ilmiah
                if ($type === '' && preg_match('/^\d+(\.\d+)?E\+\d+$/i', $v)) $v = number_format((float) $v, 0, '', '');
                $out[$col - 1] = trim($v);
            }
            if ($out) { $max = max(array_keys($out)); $rows[] = array_map(fn ($i) => $out[$i] ?? '', range(0, $max)); }
        }
        return $rows;
    }
}
