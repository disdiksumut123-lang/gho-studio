<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseTurn extends Model
{
    protected $fillable = ['case_session_id', 'round', 'role', 'content', 'meta'];
    protected function casts(): array { return ['meta' => 'array']; }
}
