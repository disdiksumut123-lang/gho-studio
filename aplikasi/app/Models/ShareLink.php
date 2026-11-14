<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Tautan bagikan per Pelatihan, Agenda, Mata Pelatihan, atau Aktivitas, dengan waktu mulai dan akhir. */
class ShareLink extends Model
{
    protected $fillable = ['token', 'training_id', 'level', 'item_id', 'label', 'opens_at', 'closes_at', 'created_by'];
    protected function casts(): array { return ['opens_at' => 'datetime', 'closes_at' => 'datetime']; }

    protected static function booted(): void
    {
        static::creating(fn (ShareLink $l) => $l->token = $l->token ?: Str::lower(Str::random(10)));
    }

    public function training() { return $this->belongsTo(Training::class); }
    public function url(): string { return route('share.open', $this->token); }

    public function state(): ?string
    {
        if ($this->opens_at && now()->lt($this->opens_at)) return 'belum';
        if ($this->closes_at && now()->gt($this->closes_at)) return 'tutup';
        return null;
    }

    public function levelLabel(): string
    {
        return match ($this->level) { 'pelatihan' => __('Pelatihan'), 'agenda' => __('Agenda'), 'mapel' => __('Mata pelatihan'), default => __('Aktivitas') };
    }
}
