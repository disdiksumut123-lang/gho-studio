<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormTemplate extends Model
{
    protected $fillable = ['name', 'training_type', 'config', 'created_by'];
    protected function casts(): array { return ['config' => 'array']; }
}
