<?php
declare(strict_types=1);

return [
    'host'    => env('DB_HOST', '127.0.0.1'),
    'port'    => (int) env('DB_PORT', 3306),
    'name'    => env('DB_NAME', 'pustisathi'),
    'user'    => env('DB_USER', 'pustisathi_app'),
    'pass'    => (string) env('DB_PASS', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),

    // migrate.php only, never the web app — the runtime DB user has no DDL
    // rights, so a SQL-injection bug in the app itself can never DROP or
    // ALTER a table.
    'migrate_user' => env('DB_MIGRATE_USER', env('DB_USER', 'root')),
    'migrate_pass' => (string) env('DB_MIGRATE_PASS', env('DB_PASS', '')),
];
