<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Pembersih HTML dari editor teks. Hanya tag dan atribut aman yang dipertahankan.
 * Persamaan disimpan sebagai <span class="eq" data-latex="..."></span> dan digambar ulang di browser.
 */
class Html
{
    private const TAGS = ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'sub', 'sup', 'h2', 'h3', 'h4', 'blockquote', 'ul', 'ol', 'li',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr', 'a', 'span', 'div', 'font', 'img'];

    public static function clean(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') return '';
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__r">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        $root = $doc->getElementById('__r') ?? $doc->documentElement;
        self::walk($root);
        $out = '';
        foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
        return $out;
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $c) {
            if ($c->nodeType === XML_COMMENT_NODE || $c->nodeType === XML_PI_NODE) { $node->removeChild($c); continue; }
            if (! $c instanceof DOMElement) continue;
            $tag = strtolower($c->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta'], true)) { $node->removeChild($c); continue; }
            if (! in_array($tag, self::TAGS, true)) {
                self::walk($c);
                while ($c->firstChild) $node->insertBefore($c->firstChild, $c);
                $node->removeChild($c);
                continue;
            }
            if ($tag === 'span' && str_contains(' '.$c->getAttribute('class').' ', ' eq ')) {
                $lx = $c->getAttribute('data-latex');
                while ($c->firstChild) $c->removeChild($c->firstChild);
                foreach (iterator_to_array($c->attributes) as $a) $c->removeAttribute($a->name);
                $c->setAttribute('class', 'eq');
                $c->setAttribute('contenteditable', 'false');
                $c->setAttribute('data-latex', mb_substr($lx, 0, 2000));
                $c->appendChild($c->ownerDocument->createTextNode(' '));
                continue;
            }
            foreach (iterator_to_array($c->attributes) as $a) {
                $n = strtolower($a->name); $v = trim($a->value);
                $keep = match (true) {
                    $n === 'href' && $tag === 'a' => (bool) preg_match('~^(https?:|mailto:)~i', $v),
                    $n === 'src' && $tag === 'img' => (bool) preg_match('~^(https://|data:image/(png|jpe?g|gif|webp);base64,)~i', $v),
                    $n === 'alt' && $tag === 'img' => true,
                    $n === 'color' && $tag === 'font' => (bool) preg_match('/^#?[0-9a-z]{3,20}$/i', $v),
                    $n === 'size' && $tag === 'font' => (bool) preg_match('/^[1-7]$/', $v),
                    in_array($n, ['colspan', 'rowspan'], true) => ctype_digit($v),
                    $n === 'style' => true,
                    default => false,
                };
                if (! $keep) { $c->removeAttribute($a->name); continue; }
                if ($n === 'style') {
                    $safe = [];
                    foreach (explode(';', $v) as $decl) {
                        [$p, $val] = array_map('trim', explode(':', $decl, 2) + [1 => '']);
                        if (in_array(strtolower($p), ['color', 'background-color', 'text-align', 'font-weight', 'font-style', 'text-decoration'], true)
                            && preg_match('/^[#a-z0-9(),.%\s-]{1,60}$/i', $val) && ! preg_match('/url|expression/i', $val)) $safe[] = strtolower($p).':'.$val;
                    }
                    $safe ? $c->setAttribute('style', implode(';', $safe)) : $c->removeAttribute('style');
                }
            }
            if ($tag === 'a') { $c->setAttribute('rel', 'noopener nofollow'); $c->setAttribute('target', '_blank'); }
            self::walk($c);
        }
    }
}
