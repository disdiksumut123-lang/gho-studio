<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    protected $fillable = ['training_id', 'name', 'position', 'opens_at', 'closes_at', 'sequential'];
    protected function casts(): array { return ['sequential' => 'boolean', 'opens_at' => 'datetime', 'closes_at' => 'datetime']; }
    public function training() { return $this->belongsTo(Training::class); }
    public function subjects() { return $this->hasMany(Subject::class)->orderBy('position'); }
}
