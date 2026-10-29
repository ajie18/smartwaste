<?php

if (getenv('VERCEL_ENV') !== 'production') {
    fwrite(STDOUT, "Skipping database migrations outside the Vercel production environment.\n");
    exit(0);
}

$requiredVariables = ['ADMIN_NAME', 'ADMIN_EMAIL', 'ADMIN_PASSWORD', 'DB_CONNECTION'];

foreach ($requiredVariables as $variable) {
    if (getenv($variable) === false || trim((string) getenv($variable)) === '') {
        fwrite(STDERR, "Missing required Vercel Production environment variable: {$variable}\n");
        exit(1);
    }
}

$adminName = trim((string) getenv('ADMIN_NAME'));
$adminEmail = trim((string) getenv('ADMIN_EMAIL'));
$adminPassword = (string) getenv('ADMIN_PASSWORD');

if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 16 || strlen($adminName) > 255) {
    fwrite(STDERR, "ADMIN_EMAIL must be valid, ADMIN_NAME must be at most 255 characters, and ADMIN_PASSWORD must be at least 16 characters.\n");
    exit(1);
}

$connection = getenv('DB_CONNECTION');
$databaseUrl = getenv('DB_URL');

if (! in_array($connection, ['mysql', 'mariadb', 'pgsql'], true)) {
    fwrite(STDERR, "DB_CONNECTION must be mysql, mariadb, or pgsql for Vercel production deployments.\n");
    exit(1);
}

if (! $databaseUrl) {
    foreach (['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $variable) {
        if (getenv($variable) === false || trim((string) getenv($variable)) === '') {
            fwrite(STDERR, "Set {$variable} or provide a complete DB_URL before deploying.\n");
            exit(1);
        }
    }
}

$commands = [
    ['artisan', 'migrate', '--force'],
    ['artisan', 'db:seed', '--class=Database\\Seeders\\AdminUserSeeder', '--force'],
];

foreach ($commands as $command) {
    $process = proc_open(
        array_merge([PHP_BINARY, ...$command]),
        [0 => STDIN, 1 => STDOUT, 2 => STDERR],
        $pipes,
        dirname(__DIR__)
    );

    if (! is_resource($process)) {
        fwrite(STDERR, "Unable to start the Laravel deployment command.\n");
        exit(1);
    }

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        exit($exitCode);
    }
}
