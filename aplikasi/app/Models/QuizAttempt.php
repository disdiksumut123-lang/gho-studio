<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = ['activity_id', 'user_id', 'answers', 'score', 'finished_at'];
    protected function casts(): array { return ['answers' => 'array', 'finished_at' => 'datetime']; }
}
