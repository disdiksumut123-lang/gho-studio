<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = ['activity_id', 'user_id', 'item_key', 'body', 'body_text', 'file_path', 'file_name', 'version', 'submitted'];
    protected function casts(): array { return ['submitted' => 'boolean']; }
    public function activity() { return $this->belongsTo(Activity::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function feedback() { return $this->hasOne(Feedback::class)->latestOfMany(); }
}
