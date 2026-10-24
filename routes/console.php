<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('app:status', function (): void {
    $this->info('Laravel application bootstrap is ready.');
})->purpose('Report application bootstrap status');