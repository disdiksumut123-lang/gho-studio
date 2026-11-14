<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = ['activity_id', 'position', 'type', 'prompt', 'data', 'answer', 'explanation'];
    protected function casts(): array { return ['data' => 'array', 'answer' => 'array']; }

    public const TYPES = [
        'mcq' => 'Pilihan ganda biasa',
        'mcq_multi' => 'Pilihan ganda kompleks',
        'tf_multi' => 'Benar/salah per pernyataan',
        'matching' => 'Menjodohkan',
        'ordering' => 'Mengurutkan',
        'short' => 'Isian singkat',
    ];

    /** Nilai satu jawaban: true jika benar. */
    public function check($given): bool
    {
        $a = $this->answer ?? [];
        switch ($this->type) {
            case 'mcq': return isset($a['correct']) && (string) $given === (string) $a['correct'];
            case 'mcq_multi':
                $g = array_map('strval', (array) $given); sort($g);
                $c = array_map('strval', (array) ($a['correct'] ?? [])); sort($c);
                return $g === $c && $c !== [];
            case 'tf_multi':
                foreach (($a['correct'] ?? []) as $i => $v) { if (! isset($given[$i]) || (bool) (int) $given[$i] !== (bool) $v) return false; }
                return true;
            case 'matching':
                foreach (($a['correct'] ?? []) as $i => $v) { if (! isset($given[$i]) || (string) $given[$i] !== (string) $v) return false; }
                return true;
            case 'ordering':
                return array_map('strval', (array) $given) === array_map('strval', (array) ($a['correct'] ?? []));
            case 'short':
                $g = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $given)));
                foreach (($a['accepted'] ?? []) as $acc) { if ($g === mb_strtolower(trim($acc))) return true; }
                return false;
        }
        return false;
    }
}
