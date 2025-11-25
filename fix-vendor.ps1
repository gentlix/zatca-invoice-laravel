# Fix corrupted vendor directory stubs
Write-Host "Fixing corrupted vendor stubs directories..." -ForegroundColor Yellow

$stubsPaths = @(
    "vendor\symfony\polyfill-php80\Resources\stubs",
    "vendor\symfony\polyfill-php83\Resources\stubs",
    "vendor\symfony\polyfill-intl-normalizer\Resources\stubs"
)

foreach ($path in $stubsPaths) {
    $fullPath = Join-Path $PSScriptRoot $path
    if (Test-Path $fullPath) {
        $item = Get-Item $fullPath -ErrorAction SilentlyContinue
        if ($item -and $item.LinkType) {
            Write-Host "Removing symlink: $path" -ForegroundColor Cyan
            Remove-Item $fullPath -Force -ErrorAction SilentlyContinue
        }
    }
    # Ensure parent directory exists
    $parentPath = Split-Path $fullPath -Parent
    if (-not (Test-Path $parentPath)) {
        New-Item -ItemType Directory -Path $parentPath -Force | Out-Null
    }
    # Create stubs as directory if it doesn't exist
    if (-not (Test-Path $fullPath)) {
        Write-Host "Creating directory: $path" -ForegroundColor Green
        New-Item -ItemType Directory -Path $fullPath -Force | Out-Null
    }
}

Write-Host "Regenerating autoloader..." -ForegroundColor Yellow
composer dump-autoload

Write-Host "Done! Try running: php artisan --version" -ForegroundColor Green

