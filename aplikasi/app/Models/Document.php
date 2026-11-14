<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['activity_id', 'user_id', 'answer_id', 'sections', 'status', 'reviewer_note', 'updated_by'];
    protected function casts(): array { return ['sections' => 'array']; }
    public function activity() { return $this->belongsTo(Activity::class); }
    public function user() { return $this->belongsTo(User::class); }
}
