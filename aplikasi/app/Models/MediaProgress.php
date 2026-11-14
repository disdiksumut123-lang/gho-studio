<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaProgress extends Model
{
    protected $table = 'media_progress';
    protected $fillable = ['activity_id', 'user_id', 'max_position', 'duration', 'pages_seen', 'active_seconds'];
    protected function casts(): array { return ['pages_seen' => 'array']; }
}
