<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['agenda_id', 'name', 'position', 'opens_at', 'closes_at', 'sequential'];
    protected function casts(): array { return ['sequential' => 'boolean', 'opens_at' => 'datetime', 'closes_at' => 'datetime']; }
    public function agenda() { return $this->belongsTo(Agenda::class); }
    public function activities() { return $this->hasMany(Activity::class)->orderBy('position'); }
}
