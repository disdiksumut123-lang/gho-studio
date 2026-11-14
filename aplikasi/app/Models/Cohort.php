<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cohort extends Model
{
    protected $fillable = ['training_id', 'name', 'join_code'];
    public function training() { return $this->belongsTo(Training::class); }
}
