<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = ['training_id', 'cohort_id', 'user_id', 'role', 'status'];
    public function training() { return $this->belongsTo(Training::class); }
    public function cohort() { return $this->belongsTo(Cohort::class); }
    public function user() { return $this->belongsTo(User::class); }
}
