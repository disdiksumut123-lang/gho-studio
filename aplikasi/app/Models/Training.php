<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Training extends Model
{
    protected $fillable = ['name', 'type', 'use_cohorts', 'join_code', 'opens_at', 'closes_at', 'sequential', 'created_by', 'access_mode'];

    protected function casts(): array
    {
        return ['use_cohorts' => 'boolean', 'sequential' => 'boolean', 'opens_at' => 'datetime', 'closes_at' => 'datetime'];
    }

    public const TYPES = ['LATSAR' => 'Latsar CPNS', 'PKP' => 'PKP', 'PKA' => 'PKA', 'PKN2' => 'PKN Tingkat II', 'LAIN' => 'Lainnya'];

    protected static function booted(): void
    {
        static::creating(function (Training $t) {
            $t->join_code = $t->join_code ?: static::newCode($t->type);
        });
    }

    public static function newCode(string $prefix = 'GHO'): string
    {
        do { $c = strtoupper(substr($prefix, 0, 3)).'-'.random_int(1000, 9999); }
        while (static::where('join_code', $c)->exists() || Cohort::where('join_code', $c)->exists());
        return $c;
    }

    public function agendas() { return $this->hasMany(Agenda::class)->orderBy('position'); }
    public function cohorts() { return $this->hasMany(Cohort::class); }
    public function enrollments() { return $this->hasMany(Enrollment::class); }
    public function participants() { return $this->belongsToMany(User::class, 'enrollments')->wherePivot('role', 'peserta')->withPivot('cohort_id'); }
}
