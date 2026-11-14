<?php

use Illuminate\Support\Facades\Schedule;

// Cron Hostinger: * * * * * php /path/artisan schedule:run
Schedule::command('gho:terbitkan')->everyMinute()->withoutOverlapping();
