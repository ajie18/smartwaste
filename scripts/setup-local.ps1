$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $projectRoot

foreach ($commandName in @('php', 'composer')) {
    if (-not (Get-Command $commandName -ErrorAction SilentlyContinue)) {
        throw "Required command '$commandName' was not found. Install PHP 8.2+ and Composer, then reopen PowerShell."
    }
}

$phpVersionOutput = php -v | Select-Object -First 1
$phpVersionMatch = [regex]::Match($phpVersionOutput, 'PHP\s+(\d+\.\d+\.\d+)')
if (-not $phpVersionMatch.Success) {
    throw 'Could not determine the installed PHP version.'
}

$phpVersion = $phpVersionMatch.Groups[1].Value
if ([version]$phpVersion -lt [version]'8.2') {
    throw "PHP 8.2 or newer is required; found PHP $phpVersion."
}

$phpModules = php -m
foreach ($requiredExtension in @('PDO', 'pdo_sqlite', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'fileinfo')) {
    if ($phpModules -notcontains $requiredExtension) {
        throw "Required PHP extension '$requiredExtension' is missing. Enable it in the active PHP installation and rerun this script."
    }
}

if (-not (Test-Path 'vendor/autoload.php')) {
    Write-Host 'Installing PHP dependencies...'
    composer install
    if ($LASTEXITCODE -ne 0) { throw 'composer install failed.' }
}

if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    Write-Host 'Created .env from .env.example.'
}

$environment = Get-Content '.env' -Raw
$connectionMatch = [regex]::Match($environment, '(?m)^DB_CONNECTION[ \t]*=[ \t]*([^\r\n#]*)')
$databaseConnection = if ($connectionMatch.Success) { $connectionMatch.Groups[1].Value.Trim().Trim('"') } else { 'sqlite' }

if ($databaseConnection -eq 'sqlite') {
    $databaseMatch = [regex]::Match($environment, '(?m)^DB_DATABASE[ \t]*=[ \t]*([^\r\n#]*)')
    $databasePath = if ($databaseMatch.Success) { $databaseMatch.Groups[1].Value.Trim().Trim('"') } else { 'database/smartwaste-local.sqlite' }

    if ($databasePath -ne ':memory:') {
        if (-not [System.IO.Path]::IsPathRooted($databasePath)) {
            $databasePath = Join-Path $projectRoot $databasePath
        }

        $databaseDirectory = Split-Path -Parent $databasePath
        if (-not (Test-Path $databaseDirectory)) {
            New-Item -ItemType Directory -Path $databaseDirectory -Force | Out-Null
        }

        if (-not (Test-Path $databasePath)) {
            $databaseFile = [System.IO.File]::Open($databasePath, [System.IO.FileMode]::CreateNew)
            $databaseFile.Dispose()
            Write-Host "Created SQLite database file: $databasePath"
        }
    }
}

$keyMatch = [regex]::Match($environment, '(?m)^APP_KEY[ \t]*=[ \t]*([^\r\n#]*)')
if (-not $keyMatch.Success -or [string]::IsNullOrWhiteSpace($keyMatch.Groups[1].Value.Trim().Trim('"'))) {
    php artisan key:generate
    if ($LASTEXITCODE -ne 0) { throw 'Could not generate the application key.' }
}

php artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Database migrations failed. Check the database settings in .env.' }

if (-not (Test-Path 'public/storage')) {
    php artisan storage:link
    if ($LASTEXITCODE -ne 0) { throw 'Could not create the public storage link.' }
}

Write-Host ''
Write-Host 'Local setup is complete. Start the app with: php artisan serve'
Write-Host 'Then open http://127.0.0.1:8000/login'