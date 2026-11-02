# LifeLink - PowerShell Server Launcher
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "   LifeLink - Standalone Server Launcher (No XAMPP)   " -ForegroundColor Yellow
Write-Host "========================================================" -ForegroundColor Cyan

# Check if mysqld is running
$mysqld = Get-Process mysqld -ErrorAction SilentlyContinue
if (-not $mysqld) {
    Write-Host "[*] Starting standalone MySQL Server..." -ForegroundColor Yellow
    Start-Process mysqld -ArgumentList "--datadir=$env:USERPROFILE\mysql-data" -WindowStyle Hidden
    Start-Sleep -Seconds 2
} else {
    Write-Host "[OK] MySQL is already running." -ForegroundColor Green
}

Write-Host "`n>>> LifeLink is live at: http://localhost:8000/ <<<`n" -ForegroundColor Green
$env:PHP_CLI_SERVER_WORKERS = "4"
php -S localhost:8000
