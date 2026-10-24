<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$status = $app->handleCommand(new Symfony\Component\Console\Input\ArgvInput([
    'artisan', 'migrate', '--force',
]));

exit($status);
