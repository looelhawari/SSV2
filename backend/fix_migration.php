<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Insert the missing migration record
DB::table('migrations')->insert([
    'migration' => '0001_01_01_000000_create_users_table',
    'batch' => 1
]);

echo "Migration record added successfully!\n";
