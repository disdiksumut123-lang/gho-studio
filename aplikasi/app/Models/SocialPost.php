<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialPost extends Model
{
    protected $fillable = ['title', 'caption', 'captions', 'platforms', 'media_path', 'media_name', 'media_type', 'scheduled_at', 'status', 'results', 'created_by'];
    protected function casts(): array { return ['captions' => 'array', 'platforms' => 'array', 'results' => 'array', 'scheduled_at' => 'datetime']; }

    /** id => [nama, warna, singkatan, jenis media yang diterima, batas karakter] */
    public const PLATFORMS = [
        'yt' => ['YouTube', '#e62117', 'YT', 'video', 5000],
        'fb' => ['Facebook', '#1877f2', 'f', 'semua', 63206],
        'ig' => ['Instagram', '#c13584', 'IG', 'semua', 2200],
        'tt' => ['TikTok', '#111111', 'TT', 'video', 2200],
        'x' => ['X', '#333333', 'X', 'semua', 280],
        'li' => ['LinkedIn', '#0a66c2', 'in', 'semua', 3000],
        'th' => ['Threads', '#222222', '@', 'semua', 500],
    ];
}
