<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    protected $fillable = ['platform', 'name', 'external_id', 'token', 'refresh_token', 'expires_at', 'enabled'];
    protected $hidden = ['token', 'refresh_token'];
    protected function casts(): array { return ['token' => 'encrypted', 'refresh_token' => 'encrypted', 'expires_at' => 'datetime', 'enabled' => 'boolean']; }
}
