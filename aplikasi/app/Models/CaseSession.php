<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseSession extends Model
{
    protected $fillable = ['activity_id', 'user_id', 'round', 'status', 'final'];
    protected function casts(): array { return ['final' => 'array']; }
    public function turns() { return $this->hasMany(CaseTurn::class)->orderBy('id'); }
    public function activity() { return $this->belongsTo(Activity::class); }
}
