<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';
    protected $fillable = ['answer_id', 'content', 'scores', 'total', 'status', 'reviewer_note', 'reviewed_by'];
    protected function casts(): array { return ['scores' => 'array']; }
    public function answer() { return $this->belongsTo(Answer::class); }
}
