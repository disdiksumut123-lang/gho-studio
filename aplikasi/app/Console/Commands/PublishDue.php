<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Services\Publisher;
use Illuminate\Console\Command;

class PublishDue extends Command
{
    protected $signature = 'gho:terbitkan';
    protected $description = 'Terbitkan konten CMS yang jadwalnya sudah tiba (dipanggil cron tiap menit)';

    public function handle(Publisher $pub): int
    {
        SocialPost::where('status', 'terjadwal')->where('scheduled_at', '<=', now())->limit(20)->get()->each(fn ($p) => $pub->publish($p));
        return self::SUCCESS;
    }
}
