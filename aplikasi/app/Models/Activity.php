<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['subject_id', 'owner_role', 'type', 'title', 'instructions', 'config', 'position', 'opens_at', 'closes_at', 'sequential', 'required', 'allow_upload'];

    protected function casts(): array
    {
        return ['config' => 'array', 'sequential' => 'boolean', 'required' => 'boolean', 'allow_upload' => 'boolean', 'opens_at' => 'datetime', 'closes_at' => 'datetime'];
    }

    /** Jenis aktivitas versi pertama. Nama tidak memakai kata "AI". */
    public const TYPES = [
        'materi' => ['Materi bacaan', 'book'],
        'video' => ['Video', 'play'],
        'audio' => ['Audiobook', 'mic'],
        'ebook' => ['eBook (Heyzine)', 'book'],
        'tugas' => ['Tugas tulis', 'pen'],
        'refleksi' => ['Refleksi', 'chat'],
        'studi_kasus' => ['Studi kasus interaktif', 'chat'],
        'kuis' => ['Kuis', 'quiz'],
        'formulir' => ['Formulir dan dokumen', 'file'],
    ];

    public function cfg(string $key, $default = null) { return data_get($this->config ?? [], $key, $default); }
    public function typeLabel(): string { return __(self::TYPES[$this->type][0] ?? $this->type); }
    public function icon(): string { return self::TYPES[$this->type][1] ?? 'file'; }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function questions() { return $this->hasMany(QuizQuestion::class)->orderBy('position'); }
    public function training(): Training { return $this->subject->agenda->training; }
}
