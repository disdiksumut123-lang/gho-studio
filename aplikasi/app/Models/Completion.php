<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Completion extends Model
{
    public $timestamps = false;
    protected $fillable = ['activity_id', 'user_id', 'completed_at'];
    protected function casts(): array { return ['completed_at' => 'datetime']; }

    public static function mark(int $activityId, int $userId): void
    {
        static::firstOrCreate(['activity_id' => $activityId, 'user_id' => $userId], ['completed_at' => now()]);
    }
}
